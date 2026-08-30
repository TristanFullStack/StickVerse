<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\TableauDeBordJoueurService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home', methods: ['GET'])]
    public function index(
        TableauDeBordJoueurService $tableauDeBordJoueurService,
    ): Response {
        $utilisateur = $this->getUser();

        return $this->render('home/index.html.twig', [
            'tableau_de_bord' => $utilisateur instanceof User
                ? $tableauDeBordJoueurService->construire($utilisateur)
                : null,
        ]);
    }
}
