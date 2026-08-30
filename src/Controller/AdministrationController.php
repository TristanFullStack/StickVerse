<?php

namespace App\Controller;

use App\Repository\CaisseRepository;
use App\Repository\CaisseStickmanRepository;
use App\Repository\StickmanRepository;
use App\Repository\UserRepository;
use App\Service\ConsoleAdministrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
final class AdministrationController extends AbstractController
{
    #[Route(name: 'app_admin_console', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        StickmanRepository $stickmanRepository,
        CaisseRepository $caisseRepository,
        CaisseStickmanRepository $caisseStickmanRepository,
        UserRepository $userRepository,
        ConsoleAdministrationService $consoleAdministrationService,
    ): Response {
        $commande = '';
        $resultat = null;

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid(
                'console-administration',
                $request->getPayload()->getString('_token'),
            )) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $commande = $request->getPayload()->getString('commande');
            $resultat = $consoleAdministrationService->executer($commande);
        }

        return $this->render('admin/index.html.twig', [
            'nombre_stickmen' => $stickmanRepository->count([]),
            'nombre_caisses' => $caisseRepository->count([]),
            'nombre_contenus_caisses' => $caisseStickmanRepository->count([]),
            'nombre_joueurs' => $userRepository->count([]),
            'commande' => $commande,
            'resultat' => $resultat,
        ]);
    }
}
