<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\ProfilJoueurService;
use App\Service\RecompenseQuotidienneService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
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

    #[Route('/profil/recompense-quotidienne', name: 'app_recompense_quotidienne', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function reclamerRecompenseQuotidienne(
        Request $request,
        RecompenseQuotidienneService $recompenseQuotidienneService,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'recompense-quotidienne',
            $request->getPayload()->getString('_token'),
        )) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $joueur = $this->getUser();

        if (!$joueur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $montant = $recompenseQuotidienneService->reclamer($joueur);

        if ($montant > 0) {
            $this->addFlash(
                'success',
                sprintf(
                    'Récompense quotidienne récupérée : +%d pièces.',
                    $montant,
                ),
            );
        } else {
            $this->addFlash(
                'error',
                'Tu as déjà récupéré ta récompense quotidienne aujourd’hui.',
            );
        }

        return $this->redirectToRoute('app_profil');
    }
}
