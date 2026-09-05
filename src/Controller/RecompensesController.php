<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ProfilJoueurService;
use App\Repository\ClassementSaisonJoueurRepository;
use App\Repository\CollectionJeuRepository;
use App\Service\DivisionClassementService;
use App\Service\RecompenseClassementSaisonService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class RecompensesController extends AbstractController
{
    #[Route('/recompenses', name: 'app_recompenses', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(
        ProfilJoueurService $profilJoueurService,
        CollectionJeuRepository $collectionJeuRepository,
        ClassementSaisonJoueurRepository $classementRepository,
        RecompenseClassementSaisonService $recompenseSaisonService,
        DivisionClassementService $divisionService,
    ): Response
    {
        $joueur = $this->getUser();
        if (!$joueur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $recompensesSaison = [];
        foreach ($collectionJeuRepository->trouverSaisonsClassees() as $saison) {
            $classement = $classementRepository->findOneBy(['joueur' => $joueur, 'saison' => $saison]);
            if ($classement === null) {
                continue;
            }
            $division = $divisionService->informationsPour($classement->getElo());
            $recompensesSaison[] = [
                'saison' => $saison,
                'classement' => $classement,
                'montant' => $division['recompense'],
                'division' => $division['nom'],
                'disponible' => $recompenseSaisonService->estDisponible($classement),
            ];
        }

        return $this->render('recompenses/index.html.twig', [
            'profil' => $profilJoueurService->construire($joueur),
            'recompensesSaison' => $recompensesSaison,
            'divisions' => $divisionService->definitions(),
        ]);
    }
}
