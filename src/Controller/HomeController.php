<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\StickmanRepository;
use App\Service\ScorePuissanceService;
use App\Service\TableauDeBordJoueurService;
use App\Service\SaisonJoueurService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home', methods: ['GET'])]
    public function index(
        TableauDeBordJoueurService $tableauDeBordJoueurService,
        SaisonJoueurService $saisonJoueurService,
        StickmanRepository $stickmanRepository,
        ScorePuissanceService $scorePuissanceService,
    ): Response {
        $utilisateur = $this->getUser();
        $disponibles = $stickmanRepository->trouverDisponibles();
        $vitrine = [];
        foreach ([2, 5, 3, 4, 1] as $rarete) {
            foreach ($disponibles as $stickman) {
                if ($stickman->getRarete() === $rarete) {
                    $vitrine[] = $stickman;
                    break;
                }
            }
            if (count($vitrine) === 3) break;
        }
        $puissances = [];
        foreach ($vitrine as $stickman) {
            $puissances[$stickman->getId()] = $scorePuissanceService->calculerStickman($stickman);
        }

        return $this->render('home/index.html.twig', [
            'vitrine' => $vitrine,
            'puissances' => $puissances,
            'tableau_de_bord' => $utilisateur instanceof User
                ? $tableauDeBordJoueurService->construire($utilisateur)
                : null,
            'saison' => $utilisateur instanceof User
                ? $saisonJoueurService->construire($utilisateur)
                : null,
        ]);
    }
}
