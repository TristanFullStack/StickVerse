<?php

namespace App\Controller;

use App\Entity\CollectionJeu;
use App\Repository\ClassementSaisonJoueurRepository;
use App\Repository\CollectionJeuRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ClassementController extends AbstractController
{
    #[Route('/classement', name: 'app_classement', methods: ['GET'])]
    public function index(
        Request $request,
        UserRepository $userRepository,
        CollectionJeuRepository $collectionRepository,
        ClassementSaisonJoueurRepository $classementSaisonRepository,
    ): Response {
        $saisonActive = $collectionRepository->trouverSaisonActive();
        $numeroSaison = $request->query->getInt('saison');
        $saisonSelectionnee = $numeroSaison > 0
            ? $collectionRepository->findOneBy([
                'saison' => $numeroSaison,
            ])
            : $saisonActive;

        return $this->render('classement/index.html.twig', [
            'joueurs' => $userRepository->trouverClassementElo(),
            'saisons' => $collectionRepository
                ->trouverSaisonsClassees(),
            'saisonActive' => $saisonActive,
            'saisonSelectionnee' => $saisonSelectionnee,
            'classementSaison' => $saisonSelectionnee instanceof CollectionJeu
                ? $classementSaisonRepository
                    ->trouverPourSaison($saisonSelectionnee)
                : [],
        ]);
    }
}
