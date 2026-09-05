<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ProfilJoueurService;
use App\Service\RecompenseQuotidienneService;
use App\Service\ObjectifJoueurService;
use App\Service\MissionsJoueurService;
use App\Service\RecompenseHoraireService;
use App\Service\LimitationActionsSensiblesService;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(
        Request $request,
        ProfilJoueurService $profilJoueurService,
    ): Response {
        $joueur = $this->getUser();

        if (!$joueur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $saison = $request->query->getInt('saison', 0);
        $saison = $saison > 0 ? $saison : null;

        return $this->render('profil/index.html.twig', [
            'profil' => $profilJoueurService->construire($joueur, $saison),
            'saisonSelectionnee' => $saison,
            'saisonsDisponibles' => [1],
        ]);
    }

    #[Route('/profil/recompense-quotidienne', name: 'app_recompense_quotidienne', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function reclamerRecompenseQuotidienne(
        Request $request,
        RecompenseQuotidienneService $recompenseQuotidienneService,
        LimitationActionsSensiblesService $limitationService,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'recompense-quotidienne',
            $request->getPayload()->getString('_token'),
        )) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $joueur = $this->getUser();

        if (!$joueur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($this->tropDeTentativesDeRecompense($joueur, $request, $limitationService)) {
            return $this->redirectToRoute('app_recompenses');
        }

        $montant = $recompenseQuotidienneService->reclamer($joueur);

        if ($montant > 0) {
            $this->addFlash(
                'success',
                sprintf(
                    'Récompense quotidienne récupérée : +%d pièces.',
                    $montant,
                ),
            );
        } else {
            $this->addFlash(
                'error',
                'Tu as déjà récupéré ta récompense quotidienne aujourd’hui.',
            );
        }

        return $this->redirectToRoute('app_recompenses');
    }

    #[Route('/profil/recompense-horaire', name: 'app_recompense_horaire', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function reclamerRecompenseHoraire(
        Request $request,
        RecompenseHoraireService $recompenseHoraireService,
        LimitationActionsSensiblesService $limitationService,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'recompense-horaire',
            $request->getPayload()->getString('_token'),
        )) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $joueur = $this->getUser();
        if (!$joueur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($this->tropDeTentativesDeRecompense($joueur, $request, $limitationService)) {
            return $this->redirectToRoute('app_recompenses');
        }

        $montant = $recompenseHoraireService->reclamer($joueur);
        $this->addFlash(
            $montant > 0 ? 'success' : 'error',
            $montant > 0
                ? sprintf('Récompense horaire récupérée : +%d pièces.', $montant)
                : 'Aucune récompense horaire disponible pour le moment.',
        );

        return $this->redirectToRoute('app_recompenses');
    }

    #[Route('/profil/objectif/{objectif}/reclamer', name: 'app_objectif_reclamer', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function reclamerObjectif(
        Request $request,
        string $objectif,
        ObjectifJoueurService $objectifJoueurService,
        LimitationActionsSensiblesService $limitationService,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'objectif-'.$objectif,
            $request->getPayload()->getString('_token'),
        )) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $joueur = $this->getUser();

        if (!$joueur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($this->tropDeTentativesDeRecompense($joueur, $request, $limitationService)) {
            return $this->redirectToRoute('app_recompenses');
        }

        try {
            $montant = $objectifJoueurService->reclamer($joueur, $objectif);
        } catch (InvalidArgumentException) {
            throw $this->createNotFoundException('Cet objectif est inconnu.');
        }

        if ($montant > 0) {
            $this->addFlash(
                'success',
                sprintf('Objectif validé : +%d pièces.', $montant),
            );
        } else {
            $this->addFlash(
                'error',
                'Cet objectif n’est pas encore disponible ou a déjà été réclamé.',
            );
        }

        return $this->redirectToRoute('app_recompenses');
    }

    #[Route('/profil/mission/{periode}/{mission}/reclamer', name: 'app_mission_reclamer', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function reclamerMission(
        Request $request,
        string $periode,
        string $mission,
        MissionsJoueurService $missionsJoueurService,
        LimitationActionsSensiblesService $limitationService,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'mission-'.$periode.'-'.$mission,
            $request->getPayload()->getString('_token'),
        )) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $joueur = $this->getUser();
        if (!$joueur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($this->tropDeTentativesDeRecompense($joueur, $request, $limitationService)) {
            return $this->redirectToRoute('app_recompenses');
        }

        try {
            $montant = $missionsJoueurService->reclamer($joueur, $periode, $mission);
        } catch (InvalidArgumentException) {
            throw $this->createNotFoundException('Cette mission est inconnue.');
        }

        $this->addFlash(
            $montant > 0 ? 'success' : 'error',
            $montant > 0
                ? sprintf('Mission validée : +%d pièces.', $montant)
                : 'Cette mission n’est pas encore terminée ou a déjà été réclamée.',
        );

        return $this->redirectToRoute('app_recompenses');
    }

    private function tropDeTentativesDeRecompense(
        User $joueur,
        Request $request,
        LimitationActionsSensiblesService $limitationService,
    ): bool {
        if ($limitationService->consommer(
            $joueur,
            'recompense',
            $request->getClientIp(),
        ) === null) {
            return false;
        }

        $this->addFlash(
            'error',
            'Trop de tentatives de récupération. Réessaie dans quelques instants.',
        );

        return true;
    }
}
