<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ClassementController extends AbstractController
{
    #[Route('/classement', name: 'app_classement', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('classement/index.html.twig', [
            'joueurs' => $userRepository->trouverClassementElo(),
        ]);
    }
}
