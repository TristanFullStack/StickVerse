<?php

namespace App\Controller;

use App\Dto\ResultatOuvertureCaisse;
use App\Entity\Caisse;
use App\Entity\Stickman;
use App\Entity\User;
use App\Exception\OuvertureCaisseImpossibleException;
use App\Exception\SoldePiecesInsuffisantException;
use App\Repository\CaisseRepository;
use App\Service\BoutiqueService;
use App\Service\OuvertureCaisseService;
use App\Service\ScorePuissanceService;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CaissePubliqueController extends AbstractController
{
    #[Route('/caisses', name: 'app_caisse_publique', methods: ['GET'])]
    #[Route('/boutique', name: 'app_boutique', methods: ['GET'])]
    public function index(CaisseRepository $caisseRepository, Request $request): Response
    {
        $caisses = $caisseRepository->trouverDisponibles();

        return $this->sansCache($this->render('caisse_publique/index.html.twig', [
            'caisses' => $caisses,
            'jetons_ouverture' => $this->genererJetons($caisses),
            'boutique' => $request->attributes->get('_route') === 'app_boutique',
        ]));
    }

    #[Route('/boutique/{id}/acheter', name: 'app_boutique_acheter', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function acheter(
        Request $request,
        Caisse $caisse,
        BoutiqueService $boutiqueService,
    ): Response {
        $this->verifierDisponibilite($caisse);
        if (!$this->isCsrfTokenValid(
            'acheter-caisse-'.$caisse->getId(),
            $request->getPayload()->getString('_token'),
        )) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $joueur = $this->getUser();
        if (!$joueur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $quantite = filter_var(
            $request->getPayload()->get('quantite', 1),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 100]],
        );
        if ($quantite === false) {
            $this->addFlash('error', 'La quantité demandée est invalide.');
            return $this->redirectToRoute('app_boutique');
        }

        try {
            $boutiqueService->acheter($joueur, $caisse, $quantite);
            $this->addFlash('success', 'La caisse a été ajoutée à ton inventaire.');
        } catch (SoldePiecesInsuffisantException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }

        return $this->redirectToRoute('app_boutique');
    }

    #[Route('/caisses/{id}', name: 'app_caisse_publique_show', methods: ['GET'])]
    public function show(Caisse $caisse): Response
    {
        $this->verifierDisponibilite($caisse);

        $contenus = $caisse->getContenus()->toArray();
        usort($contenus, static fn ($a, $b): int => ($b->getProbabilite() <=> $a->getProbabilite()));

        return $this->sansCache($this->render('caisse_publique/show.html.twig', [
            'caisse' => $caisse,
            'contenus' => $contenus,
            'jeton_ouverture' => bin2hex(random_bytes(32)),
        ]));
    }

    #[Route('/caisses/{id}/ouvrir', name: 'app_caisse_ouvrir', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function ouvrir(
        Request $request,
        Caisse $caisse,
        OuvertureCaisseService $ouvertureCaisseService,
        ScorePuissanceService $scorePuissanceService,
    ): Response {
        $this->verifierDisponibilite($caisse);
        $requeteJson = $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept'), 'application/json');

        if (!$this->isCsrfTokenValid(
            'ouvrir'.$caisse->getId(),
            $request->getPayload()->getString('_token'),
        )) {
            if ($requeteJson) {
                return $this->reponseJsonErreur('Jeton CSRF invalide.', Response::HTTP_FORBIDDEN);
            }

            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $utilisateur = $this->getUser();
        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        try {
            $resultat = $ouvertureCaisseService->ouvrirAvecJeton(
                $caisse,
                $utilisateur,
                $request->getPayload()->getString('_ouverture'),
            );
        } catch (SoldePiecesInsuffisantException $exception) {
            if ($requeteJson) {
                return $this->reponseJsonErreur($exception->getMessage(), Response::HTTP_CONFLICT);
            }

            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_caisse_publique');
        } catch (OuvertureCaisseImpossibleException $exception) {
            if ($requeteJson) {
                return $this->reponseJsonErreur($exception->getMessage(), Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_caisse_publique');
        }

        if ($requeteJson) {
            $reponse = $this->json($this->normaliserResultat(
                $resultat,
                $scorePuissanceService,
            ));
            $reponse->setPrivate();
            $reponse->setMaxAge(0);
            $reponse->headers->addCacheControlDirective('no-store');

            return $reponse;
        }

        $this->addFlash(
            'success',
            'Caisse ouverte. Retrouve le résultat dans ta collection.',
        );

        return $this->redirectToRoute('app_caisse_publique');
    }

    private function verifierDisponibilite(Caisse $caisse): void
    {
        if (
            !$caisse->isStatutActif()
            || $caisse->getCollectionJeu() !== null
                && !$caisse->getCollectionJeu()->estDisponibleA(new DateTimeImmutable())
        ) {
            throw $this->createNotFoundException('Cette caisse est indisponible.');
        }
    }

    /**
     * @param list<Caisse> $caisses
     * @return array<int, string>
     */
    private function genererJetons(array $caisses): array
    {
        if (!$this->getUser() instanceof User) {
            return [];
        }

        $jetons = [];
        foreach ($caisses as $caisse) {
            if ($caisse->getId() !== null) {
                $jetons[$caisse->getId()] = bin2hex(random_bytes(32));
            }
        }

        return $jetons;
    }

    /** @return array<string, mixed> */
    private function normaliserResultat(
        ResultatOuvertureCaisse $resultat,
        ScorePuissanceService $scorePuissanceService,
    ): array {
        $collection = $resultat->caisse->getCollectionJeu();

        return [
            'ok' => true,
            'openingId' => $resultat->ouvertureId,
            'replayed' => $resultat->rejouee,
            'crate' => [
                'id' => $resultat->caisse->getId(),
                'name' => $resultat->caisse->getNom(),
                'price' => max(0, (int) $resultat->caisse->getPrix()),
            ],
            'roulette' => array_map(
                fn (Stickman $stickman): array => $this->normaliserStickman(
                    $stickman,
                    $scorePuissanceService,
                ),
                $resultat->stickmenDisponibles,
            ),
            'reward' => [
                ...$this->normaliserStickman($resultat->stickman, $scorePuissanceService),
                'isNew' => $resultat->nouveau,
                'quantity' => $resultat->quantiteApres,
            ],
            'collection' => [
                'name' => $collection?->getNom() ?? 'Catalogue général',
                'owned' => $resultat->collectionPossedes,
                'total' => $resultat->collectionTotal,
                'complete' => $resultat->nouveau
                    && $resultat->collectionTotal > 0
                    && $resultat->collectionPossedes >= $resultat->collectionTotal,
            ],
            'wallet' => [
                'pieces' => $resultat->soldePieces,
                'freeCrates' => $resultat->caissesOffertesRestantes,
                'ownedCrates' => $resultat->caissesPossedeesRestantes,
            ],
            'canOpenAgain' => $resultat->peutOuvrirEncore,
            'nextOpeningToken' => bin2hex(random_bytes(32)),
            'inventoryUrl' => $this->generateUrl('app_inventaire'),
        ];
    }

    /** @return array<string, mixed> */
    private function normaliserStickman(
        Stickman $stickman,
        ScorePuissanceService $scorePuissanceService,
    ): array {
        return [
            'id' => $stickman->getId(),
            'name' => $stickman->getNom(),
            'slug' => $stickman->getSlug(),
            'image' => '/images/stickmen/'.rawurlencode((string) $stickman->getImage()),
            'rarity' => max(1, min(5, (int) $stickman->getRarete())),
            'role' => 'Non défini',
            'power' => $scorePuissanceService->calculerStickman($stickman),
            'hp' => $stickman->getPv(),
            'attack' => $stickman->getAttaque(),
            'defense' => $stickman->getDefense(),
            'passives' => [],
            'wikiUrl' => $this->generateUrl('app_wiki_show', [
                'slug' => $stickman->getSlug(),
            ]),
        ];
    }

    private function reponseJsonErreur(string $message, int $statut): JsonResponse
    {
        $reponse = $this->json([
            'ok' => false,
            'error' => $message,
        ], $statut);
        $reponse->setPrivate();
        $reponse->setMaxAge(0);
        $reponse->headers->addCacheControlDirective('no-store');

        return $reponse;
    }

    private function sansCache(Response $reponse): Response
    {
        $reponse->setPrivate();
        $reponse->setMaxAge(0);
        $reponse->setSharedMaxAge(0);
        $reponse->headers->addCacheControlDirective('no-store');
        $reponse->headers->addCacheControlDirective('must-revalidate');

        return $reponse;
    }
}
