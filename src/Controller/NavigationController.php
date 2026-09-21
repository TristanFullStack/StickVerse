<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class NavigationController extends AbstractController
{
    #[Route('/decouvrir', name: 'app_decouvrir', methods: ['GET'])]
    public function decouvrir(): Response
    {
        return $this->render('navigation/decouvrir.html.twig');
    }

    #[Route('/hub', name: 'app_hub', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function hub(): Response
    {
        return $this->render('navigation/hub.html.twig');
    }
}
