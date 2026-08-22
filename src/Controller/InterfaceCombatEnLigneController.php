<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class InterfaceCombatEnLigneController extends AbstractController
{
    #[Route(
        '/combats',
        name: 'app_combats_en_ligne',
        methods: ['GET']
    )]
    public function index(): Response
    {
        return $this->render(
            'combat_en_ligne/index.html.twig'
        );
    }
}
