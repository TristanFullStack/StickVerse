<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ProfilJoueurService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ProfilController extends AbstractController
{
    #[Route('/profil', name: 'app_profil', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(
        ProfilJoueurService $profilJoueurService,
    ): Response {
        $joueur = $this->getUser();

        if (!$joueur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('profil/index.html.twig', [
            'profil' => $profilJoueurService->construire($joueur),
        ]);
    }
}
