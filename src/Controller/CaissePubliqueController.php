<?php

namespace App\Controller;

use App\Entity\Caisse;
use App\Entity\User;
use App\Repository\CaisseRepository;
use App\Service\OuvertureCaisseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CaissePubliqueController extends AbstractController
{
    #[Route('/caisses', name: 'app_caisse_publique', methods: ['GET'])]
    public function index(CaisseRepository $caisseRepository): Response
    {
        $caisses = $caisseRepository->findBy([
            'statutActif' => true,
        ]);

        return $this->render('caisse_publique/index.html.twig', [
            'caisses' => $caisses,
        ]);
    }

    #[Route('/caisses/{id}/ouvrir', name: 'app_caisse_ouvrir', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function ouvrir(
        Request $request,
        Caisse $caisse,
        OuvertureCaisseService $ouvertureCaisseService
    ): Response {
        if (!$caisse->isStatutActif()) {
            throw $this->createNotFoundException(
                'Cette caisse est indisponible.'
            );
        }

        if (!$this->isCsrfTokenValid(
            'ouvrir'.$caisse->getId(),
            $request->getPayload()->getString('_token')
        )) {
            throw $this->createAccessDeniedException(
                'Jeton CSRF invalide.'
            );
        }

        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $stickman = $ouvertureCaisseService->ouvrir(
            $caisse,
            $utilisateur
        );

        if ($stickman === null) {
            $this->addFlash(
                'error',
                'Cette caisse ne contient aucun Stickman.'
            );
        } else {
            $this->addFlash(
                'success',
                'Vous avez obtenu : '.$stickman->getNom()
            );
        }

        return $this->redirectToRoute('app_caisse_publique');
    }
}