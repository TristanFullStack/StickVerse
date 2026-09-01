<?php

namespace App\Controller;

use App\Entity\Combat;
use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Repository\EquipeRepository;
use App\Service\CreationCombatEnLigneService;
use App\Service\ExpirationCombatEnAttenteService;
use App\Service\LimitationTentativesInvitationCombatService;
use App\Service\MatchmakingCombatEnLigneService;
use App\Service\RejoindreCombatEnLigneService;
use App\Service\ScorePuissanceService;
use InvalidArgumentException;
use JsonException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(
    '/salon-combat-en-ligne',
    name: 'app_salon_combat_en_ligne_'
)]
#[IsGranted('ROLE_USER')]
final class SalonCombatEnLigneController extends AbstractController
{
    #[Route(
        '',
        name: 'etat',
        methods: ['GET']
    )]
    public function etat(
        CombatRepository $combatRepository,
        EquipeRepository $equipeRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
        ExpirationCombatEnAttenteService $expirationService,
        ScorePuissanceService $scorePuissanceService,
    ): JsonResponse {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $combatActif = $combatRepository
            ->trouverActifPourJoueur($utilisateur);

        if (
            $combatActif instanceof Combat
            && $combatActif->getId() !== null
            && $expirationService->expirerSiNecessaire(
                $combatActif->getId()
            )
        ) {
            $combatActif = null;
        }

        $combatsDisponibles = array_values(
            array_filter(
                $combatRepository
                    ->trouverDisponiblesPour($utilisateur),
                static fn (Combat $combat): bool =>
                    $combat->getId() === null
                    || !$expirationService->expirerSiNecessaire(
                        $combat->getId()
                    ),
            )
        );

        $equipes = $equipeRepository->findBy(
            [
                'utilisateur' => $utilisateur,
            ],
            [
                'id' => 'ASC',
            ],
        );

        $historiqueCombats = $combatRepository
            ->trouverHistoriquePourJoueur($utilisateur);

        return $this->json([
            'combatActifId' => $combatActif?->getId(),
            'equipes' => array_map(
                fn (
                    Equipe $equipe,
                ): array => $this->serialiserEquipe(
                    $equipe,
                    $scorePuissanceService,
                ),
                $equipes,
            ),
            'combatsDisponibles' => array_map(
                fn (
                    Combat $combat,
                ): array => [
                    'id' => $combat->getId(),
                    'joueur1Id' => $combat
                        ->getJoueur1()
                        ->getId(),
                    'joueur1Pseudo' => $combat
                        ->getJoueur1()
                        ->getPseudo(),
                    'joueur1Elo' => $combat
                        ->getJoueur1()
                        ->getElo(),
                    'puissanceEquipe' => $scorePuissanceService
                        ->calculerCombatPourJoueur(
                            $combat,
                            $combat->getJoueur1(),
                        ),
                    'statut' => $combat->getStatut(),
                    'prive' => $combat->estPrive(),
                    'numeroRound' => $combat
                        ->getNumeroRound(),
                    'dateCreation' => $combat
                        ->getDateCreation()
                        ->format(DATE_ATOM),
                ],
                $combatsDisponibles,
            ),
            'historiqueCombats' => array_map(
                fn (Combat $combat): array =>
                    $this->serialiserCombatHistorique(
                        $combat,
                        $utilisateur,
                    ),
                $historiqueCombats,
            ),
            'csrf' => [
                'creer' => $csrfTokenManager
                    ->getToken(
                        $this->creerIdentifiantCsrf(
                            'creer'
                        )
                    )
                    ->getValue(),
                'rejoindre' => $csrfTokenManager
                    ->getToken(
                        $this->creerIdentifiantCsrf(
                            'rejoindre'
                        )
                    )
                    ->getValue(),
                'matchmaking' => $csrfTokenManager
                    ->getToken(
                        $this->creerIdentifiantCsrf(
                            'matchmaking'
                        )
                    )
                    ->getValue(),
            ],
        ]);
    }

    /**
     * @return array{
     *     id: int|null,
     *     nom: string|null,
     *     puissance: int,
     *     combattants: list<array{
     *         slot: string,
     *         stickmanId: int|null,
     *         nom: string|null,
     *         image: string|null,
     *         rarete: int|null,
     *         pv: int|null,
     *         attaque: int|null,
     *         defense: int|null,
     *         puissance: int
     *     }>
     * }
     */
    private function serialiserEquipe(
        Equipe $equipe,
        ScorePuissanceService $scorePuissanceService,
    ): array {
        $combattants = [];

        $stickmenParSlot = [
            'A' => $equipe->getStickmanA(),
            'B' => $equipe->getStickmanB(),
            'C' => $equipe->getStickmanC(),
            'D' => $equipe->getStickmanD(),
        ];

        foreach ($stickmenParSlot as $slot => $stickman) {
            if (!$stickman instanceof Stickman) {
                continue;
            }

            $combattants[] = [
                'slot' => $slot,
                'stickmanId' => $stickman->getId(),
                'nom' => $stickman->getNom(),
                'image' => $stickman->getImage(),
                'rarete' => $stickman->getRarete(),
                'pv' => $stickman->getPv(),
                'attaque' => $stickman->getAttaque(),
                'defense' => $stickman->getDefense(),
                'puissance' => $scorePuissanceService
                    ->calculerStickman($stickman),
            ];
        }

        return [
            'id' => $equipe->getId(),
            'nom' => $equipe->getNom(),
            'puissance' => $scorePuissanceService->calculerEquipe($equipe),
            'combattants' => $combattants,
        ];
    }

    #[Route(
        '/creer',
        name: 'creer',
        methods: ['POST']
    )]
    public function creer(
        Request $request,
        EquipeRepository $equipeRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
        CreationCombatEnLigneService $creationService,
    ): JsonResponse {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (
            !$this->csrfEstValide(
                'creer',
                $request,
                $csrfTokenManager,
            )
        ) {
            return $this->json(
                [
                    'erreur' => 'Le jeton CSRF de création est invalide.',
                ],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $donnees = $request->toArray();
        } catch (JsonException) {
            return $this->json(
                [
                    'erreur' => 'Le contenu JSON est invalide.',
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $equipeId = $this->lireEquipeId($donnees);
        } catch (InvalidArgumentException $exception) {
            return $this->json(
                [
                    'erreur' => $exception->getMessage(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $equipe = $equipeRepository->findOneBy([
            'id' => $equipeId,
            'utilisateur' => $utilisateur,
        ]);

        if (!$equipe instanceof Equipe) {
            return $this->json(
                [
                    'erreur' => 'L’équipe demandée est introuvable.',
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        try {
            $combat = $creationService->creer(
                $utilisateur,
                $equipe,
                $this->lireCombatPrive($donnees),
            );
        } catch (LogicException $exception) {
            return $this->json(
                [
                    'erreur' => $exception->getMessage(),
                ],
                Response::HTTP_CONFLICT,
            );
        }

        return $this->json(
            [
                'etat' => 'combat_cree',
                'combatId' => $combat->getId(),
                'codeInvitation' => $combat->getCodeInvitation(),
                'prive' => $combat->estPrive(),
                'statut' => $combat->getStatut(),
                'numeroRound' => $combat
                    ->getNumeroRound(),
            ],
            Response::HTTP_CREATED,
        );
    }

    #[Route(
        '/{id}/rejoindre',
        name: 'rejoindre',
        methods: ['POST']
    )]
    public function rejoindre(
        int $id,
        Request $request,
        CombatRepository $combatRepository,
        EquipeRepository $equipeRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
        RejoindreCombatEnLigneService $rejoindreService,
    ): JsonResponse {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (
            !$this->csrfEstValide(
                'rejoindre',
                $request,
                $csrfTokenManager,
            )
        ) {
            return $this->json(
                [
                    'erreur' => 'Le jeton CSRF de jonction est invalide.',
                ],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $donnees = $request->toArray();
        } catch (JsonException) {
            return $this->json(
                [
                    'erreur' => 'Le contenu JSON est invalide.',
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $combat = $combatRepository->find($id);

        if ($combat instanceof Combat && !$combat->estPrive()) {
            return $this->json(
                [
                    'erreur' => 'Les combats publics doivent être rejoints par la recherche automatique.',
                ],
                Response::HTTP_CONFLICT,
            );
        }

        return $this->traiterJonction(
            $id,
            $donnees,
            $utilisateur,
            $equipeRepository,
            $rejoindreService,
        );
    }

    #[Route(
        '/rechercher-adversaire',
        name: 'rechercher_adversaire',
        methods: ['POST']
    )]
    public function rechercherAdversaire(
        Request $request,
        EquipeRepository $equipeRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
        MatchmakingCombatEnLigneService $matchmakingService,
    ): JsonResponse {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (
            !$this->csrfEstValide(
                'matchmaking',
                $request,
                $csrfTokenManager,
            )
        ) {
            return $this->json(
                ['erreur' => 'Le jeton CSRF de recherche est invalide.'],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $donnees = $request->toArray();
            $equipeId = $this->lireEquipeId($donnees);
        } catch (JsonException) {
            return $this->json(
                ['erreur' => 'Le contenu JSON est invalide.'],
                Response::HTTP_BAD_REQUEST,
            );
        } catch (InvalidArgumentException $exception) {
            return $this->json(
                ['erreur' => $exception->getMessage()],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $equipe = $equipeRepository->findOneBy([
            'id' => $equipeId,
            'utilisateur' => $utilisateur,
        ]);

        if (!$equipe instanceof Equipe) {
            return $this->json(
                ['erreur' => 'L’équipe demandée est introuvable.'],
                Response::HTTP_NOT_FOUND,
            );
        }

        try {
            $combat = $matchmakingService->rechercher(
                $utilisateur,
                $equipe,
            );
        } catch (LogicException $exception) {
            return $this->json(
                ['erreur' => $exception->getMessage()],
                Response::HTTP_CONFLICT,
            );
        }

        $adversaireTrouve = $combat->getJoueur2() instanceof User;

        return $this->json(
            [
                'etat' => $adversaireTrouve
                    ? 'adversaire_trouve'
                    : 'recherche_lancee',
                'combatId' => $combat->getId(),
                'statut' => $combat->getStatut(),
                'numeroRound' => $combat->getNumeroRound(),
            ],
            $adversaireTrouve
                ? Response::HTTP_OK
                : Response::HTTP_CREATED,
        );
    }

    #[Route(
        '/rejoindre-par-code',
        name: 'rejoindre_par_code',
        methods: ['POST']
    )]
    public function rejoindreParCode(
        Request $request,
        CombatRepository $combatRepository,
        EquipeRepository $equipeRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
        LimitationTentativesInvitationCombatService $limitationService,
        RejoindreCombatEnLigneService $rejoindreService,
    ): JsonResponse {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (
            !$this->csrfEstValide(
                'rejoindre',
                $request,
                $csrfTokenManager,
            )
        ) {
            return $this->json(
                [
                    'erreur' => 'Le jeton CSRF de jonction est invalide.',
                ],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $donnees = $request->toArray();
            $code = $this->lireCodeInvitation($donnees);
        } catch (JsonException) {
            return $this->json(
                [
                    'erreur' => 'Le contenu JSON est invalide.',
                ],
                Response::HTTP_BAD_REQUEST,
            );
        } catch (InvalidArgumentException $exception) {
            return $this->json(
                [
                    'erreur' => $exception->getMessage(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $dateReessai = $limitationService->consommer(
            $utilisateur,
            $request->getClientIp(),
        );

        if ($dateReessai !== null) {
            $attenteSecondes = max(
                1,
                $dateReessai->getTimestamp() - time(),
            );

            $reponse = $this->json(
                [
                    'erreur' => sprintf(
                        'Trop de codes ont été essayés. Réessaie dans %d seconde%s.',
                        $attenteSecondes,
                        $attenteSecondes > 1 ? 's' : '',
                    ),
                    'reessaiDans' => $attenteSecondes,
                ],
                Response::HTTP_TOO_MANY_REQUESTS,
            );
            $reponse->headers->set(
                'Retry-After',
                (string) $attenteSecondes,
            );

            return $reponse;
        }

        $combat = $combatRepository->trouverParCodeInvitation($code);
        $combatId = $combat?->getId();

        if ($combatId === null) {
            return $this->json(
                [
                    'erreur' => 'Aucun combat ne correspond à ce code.',
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        return $this->traiterJonction(
            $combatId,
            $donnees,
            $utilisateur,
            $equipeRepository,
            $rejoindreService,
            true,
        );
    }

    /**
     * @param array<string, mixed> $donnees
     */
    private function lireEquipeId(
        array $donnees,
    ): int {
        $equipeId = $donnees['equipeId'] ?? null;

        if (
            !is_int($equipeId)
            || $equipeId <= 0
        ) {
            throw new InvalidArgumentException(
                'L’identifiant de l’équipe est invalide.'
            );
        }

        return $equipeId;
    }

    /**
     * @param array<string, mixed> $donnees
     */
    private function lireCodeInvitation(array $donnees): string
    {
        $code = $donnees['code'] ?? null;

        if (!is_string($code)) {
            throw new InvalidArgumentException(
                'Le code d’invitation est invalide.'
            );
        }

        $code = strtoupper(trim($code));

        if (preg_match('/^SV-[A-F0-9]{6}$/', $code) !== 1) {
            throw new InvalidArgumentException(
                'Le code d’invitation doit respecter le format SV-XXXXXX.'
            );
        }

        return $code;
    }

    /**
     * @param array<string, mixed> $donnees
     */
    private function lireCombatPrive(array $donnees): bool
    {
        return ($donnees['prive'] ?? false) === true;
    }

    /**
     * @param array<string, mixed> $donnees
     */
    private function traiterJonction(
        int $combatId,
        array $donnees,
        User $utilisateur,
        EquipeRepository $equipeRepository,
        RejoindreCombatEnLigneService $rejoindreService,
        bool $avecCodeInvitation = false,
    ): JsonResponse {
        try {
            $equipeId = $this->lireEquipeId($donnees);
        } catch (InvalidArgumentException $exception) {
            return $this->json(
                [
                    'erreur' => $exception->getMessage(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $equipe = $equipeRepository->findOneBy([
            'id' => $equipeId,
            'utilisateur' => $utilisateur,
        ]);

        if (!$equipe instanceof Equipe) {
            return $this->json(
                [
                    'erreur' => 'L’équipe demandée est introuvable.',
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        try {
            $combat = $rejoindreService->rejoindre(
                $combatId,
                $utilisateur,
                $equipe,
                $avecCodeInvitation,
            );
        } catch (LogicException $exception) {
            return $this->json(
                [
                    'erreur' => $exception->getMessage(),
                ],
                Response::HTTP_CONFLICT,
            );
        }

        return $this->json([
            'etat' => 'combat_rejoint',
            'combatId' => $combat->getId(),
            'statut' => $combat->getStatut(),
            'numeroRound' => $combat->getNumeroRound(),
        ]);
    }

    /**
     * @return array{
     *     id: int|null,
     *     adversairePseudo: string|null,
     *     statut: string,
     *     resultat: string,
     *     nombreRounds: int,
     *     dateFin: string
     * }
     */
    private function serialiserCombatHistorique(
        Combat $combat,
        User $utilisateur,
    ): array {
        $utilisateurId = $utilisateur->getId();
        $estJoueur1 = $combat->getJoueur1()->getId()
            === $utilisateurId;
        $adversaire = $estJoueur1
            ? $combat->getJoueur2()
            : $combat->getJoueur1();
        $gagnantId = $combat->getGagnant()?->getId();

        if ($combat->getStatut() === Combat::STATUT_ABANDONNE) {
            $resultat = $gagnantId === $utilisateurId
                ? 'victoire_abandon'
                : 'abandon';
        } elseif ($combat->getStatut() === Combat::STATUT_FORFAIT) {
            $resultat = $gagnantId === $utilisateurId
                ? 'victoire_forfait'
                : 'forfait';
        } elseif ($gagnantId === null) {
            $resultat = 'egalite';
        } else {
            $resultat = $gagnantId === $utilisateurId
                ? 'victoire'
                : 'defaite';
        }

        return [
            'id' => $combat->getId(),
            'adversairePseudo' => $adversaire?->getPseudo(),
            'statut' => $combat->getStatut(),
            'resultat' => $resultat,
            'nombreRounds' => $combat->getDernierRoundResolu() ?? 0,
            'dateFin' => $combat->getDateMiseAJour()->format(DATE_ATOM),
        ];
    }

    private function csrfEstValide(
        string $action,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): bool {
        $valeurJeton = $request->headers->get(
            'X-CSRF-TOKEN'
        );

        if (!is_string($valeurJeton)) {
            return false;
        }

        return $csrfTokenManager->isTokenValid(
            new CsrfToken(
                $this->creerIdentifiantCsrf(
                    $action
                ),
                $valeurJeton,
            )
        );
    }

    private function creerIdentifiantCsrf(
        string $action,
    ): string {
        return 'salon_combat_'.$action;
    }
}
