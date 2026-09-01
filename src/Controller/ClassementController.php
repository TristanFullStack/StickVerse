<?php

namespace App\Controller;

use App\Entity\CollectionJeu;
use App\Entity\User;
use App\Repository\ClassementSaisonJoueurRepository;
use App\Repository\CollectionJeuRepository;
use App\Repository\UserRepository;
use App\Service\DivisionClassementService;
use App\Service\RecompenseClassementSaisonService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ClassementController extends AbstractController
{
    #[Route('/classement', name: 'app_classement', methods: ['GET'])]
    public function index(
        Request $request,
        UserRepository $userRepository,
        CollectionJeuRepository $collectionRepository,
        ClassementSaisonJoueurRepository $classementSaisonRepository,
        DivisionClassementService $divisionService,
        RecompenseClassementSaisonService $recompenseService,
    ): Response {
        $saisonActive = $collectionRepository->trouverSaisonActive();
        $numeroSaison = $request->query->getInt('saison');
        $saisonSelectionnee = $numeroSaison > 0
            ? $collectionRepository->findOneBy([
                'saison' => $numeroSaison,
            ])
            : $saisonActive;

        $classementsSaison = $saisonSelectionnee instanceof CollectionJeu
            ? $classementSaisonRepository
                ->trouverPourSaison($saisonSelectionnee)
            : [];
        $divisionsSaison = [];
        $classementJoueur = null;
        $joueur = $this->getUser();

        foreach ($classementsSaison as $classement) {
            $divisionsSaison[$classement->getId()] = $divisionService
                ->informationsPour($classement->getElo());

            if (
                $joueur instanceof User
                && $classement->getJoueur()->getId() === $joueur->getId()
            ) {
                $classementJoueur = $classement;
            }
        }

        return $this->render('classement/index.html.twig', [
            'joueurs' => $userRepository->trouverClassementElo(),
            'saisons' => $collectionRepository
                ->trouverSaisonsClassees(),
            'saisonActive' => $saisonActive,
            'saisonSelectionnee' => $saisonSelectionnee,
            'classementSaison' => $classementsSaison,
            'divisionsSaison' => $divisionsSaison,
            'classementJoueur' => $classementJoueur,
            'recompenseJoueurDisponible' => $classementJoueur !== null
                && $recompenseService->estDisponible($classementJoueur),
        ]);
    }

    #[Route('/classement/saison/{saison}/recompense', name: 'app_classement_saison_recompense', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function reclamerRecompenseSaison(
        Request $request,
        CollectionJeu $saison,
        RecompenseClassementSaisonService $recompenseService,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'recompense-saison-'.$saison->getId(),
            $request->getPayload()->getString('_token'),
        )) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $joueur = $this->getUser();

        if (!$joueur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $montant = $recompenseService->reclamer($joueur, $saison);

        if ($montant > 0) {
            $this->addFlash(
                'success',
                sprintf(
                    'Récompense de la Saison %d récupérée : +%d pièces.',
                    $saison->getSaison(),
                    $montant,
                ),
            );
        } else {
            $this->addFlash(
                'error',
                'Cette récompense est indisponible ou a déjà été récupérée.',
            );
        }

        return $this->redirectToRoute('app_classement', [
            'saison' => $saison->getSaison(),
        ]);
    }
}
