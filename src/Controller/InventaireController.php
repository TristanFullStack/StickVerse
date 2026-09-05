<?php

namespace App\Controller;

use App\Entity\Caisse;
use App\Entity\User;
use App\Repository\CaisseRepository;
use App\Repository\EquipeRepository;
use App\Repository\InventaireRepository;
use App\Service\InventaireCaisseService;
use App\Service\LimitationActionsSensiblesService;
use App\Service\ScorePuissanceService;
use App\Service\VenteInventaireService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class InventaireController extends AbstractController
{
    #[Route('/inventaire', name: 'app_inventaire', methods: ['GET'])]
    public function index(
        Request $request,
        InventaireRepository $inventaireRepository,
        InventaireCaisseService $inventaireCaisseService,
        CaisseRepository $caisseRepository,
        EquipeRepository $equipeRepository,
        ScorePuissanceService $scorePuissanceService,
    ): Response {
        $joueur = $this->joueur();
        $inventaires = array_values(array_filter(
            $inventaireRepository->findBy(['utilisateur' => $joueur]),
            static fn ($inventaire): bool => ($inventaire->getQuantite() ?? 0) > 0,
        ));
        usort($inventaires, static function ($a, $b) use ($scorePuissanceService): int {
            $difference = $scorePuissanceService->calculerStickman($b->getStickman())
                <=> $scorePuissanceService->calculerStickman($a->getStickman());
            return $difference !== 0 ? $difference : strcasecmp($a->getStickman()->getNom() ?? '', $b->getStickman()->getNom() ?? '');
        });

        $puissances = [];
        $prixVente = [];
        foreach ($inventaires as $inventaire) {
            $stickman = $inventaire->getStickman();
            if ($stickman?->getId() !== null) {
                $puissance = $scorePuissanceService->calculerStickman($stickman);
                $puissances[$stickman->getId()] = $puissance;
                $prixVente[$inventaire->getId() ?? 0] = $puissance * max(1, (int) $stickman->getRarete());
            }
        }

        $caisses = $inventaireCaisseService->lister($joueur);
        // Compatibilité des comptes créés avant l’inventaire individuel :
        // leurs cinq caisses de départ restent ouvrables, sans ancien libellé.
        $caisseDepart = $caisseRepository->findOneBy(['slug' => Caisse::SLUG_PREMIERS_RENFORTS]);
        $caissesLegacy = $joueur->getCaissesPremiersRenforts() > 0 && $caisseDepart !== null
            ? array_fill(0, $joueur->getCaissesPremiersRenforts(), null)
            : [];

        $jetonsOuverture = [];
        foreach ($caisses as $possession) {
            if ($possession->getId() !== null) {
                $jetonsOuverture[(string) $possession->getId()] = bin2hex(random_bytes(32));
            }
        }
        foreach ($caissesLegacy as $index => $_) {
            $jetonsOuverture['legacy-'.$index] = bin2hex(random_bytes(32));
        }

        return $this->render('inventaire/index.html.twig', [
            'inventaires' => $inventaires,
            'puissances' => $puissances,
            'prix_vente' => $prixVente,
            'stickman_ids_equipes' => $equipeRepository->stickmanIdsUtilisesPourUtilisateur($joueur),
            'caisses' => $caisses,
            'caisse_depart' => $caisseDepart,
            'caisses_legacy' => $caissesLegacy,
            'jetons_ouverture' => $jetonsOuverture,
            'mode_vente' => $request->query->getBoolean('vente'),
        ]);
    }

    #[Route('/inventaire/vendre', name: 'app_inventaire_vendre', methods: ['POST'])]
    public function vendre(
        Request $request,
        VenteInventaireService $venteInventaireService,
        LimitationActionsSensiblesService $limitationService,
    ): Response
    {
        if (!$this->isCsrfTokenValid('vendre-inventaire', $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        if ($limitationService->secondesAvant(
            $limitationService->consommer(
                $this->joueur(),
                'inventaire_vente',
                $request->getClientIp(),
            ),
        ) > 0) {
            $this->addFlash(
                'error',
                'Trop de tentatives de vente. Réessaie dans quelques instants.',
            );

            return $this->redirectToRoute('app_inventaire', ['vente' => 1]);
        }
        $ventes = $request->getPayload()->all('ventes');
        if (!is_array($ventes)) {
            $ventes = [];
        }
        try {
            $total = $venteInventaireService->vendre($this->joueur(), $ventes);
            $this->addFlash('success', sprintf('%d pièce(s) récupérée(s) grâce à la vente.', $total));
        } catch (\Throwable $exception) {
            $this->addFlash('error', $exception->getMessage());
            return $this->redirectToRoute('app_inventaire', ['vente' => 1]);
        }
        return $this->redirectToRoute('app_inventaire');
    }

    private function joueur(): User
    {
        $joueur = $this->getUser();
        if (!$joueur instanceof User) {
            throw $this->createAccessDeniedException();
        }
        return $joueur;
    }
}
