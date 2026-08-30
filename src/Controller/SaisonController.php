<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\SaisonJoueurService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SaisonController extends AbstractController
{
    #[Route('/saison', name: 'app_saison', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(SaisonJoueurService $saisonJoueurService): Response
    {
        $joueur = $this->getUser();
        if (!$joueur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('saison/index.html.twig', [
            'saison' => $saisonJoueurService->construire($joueur),
        ]);
    }
}
