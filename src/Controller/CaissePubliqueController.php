<?php

namespace App\Controller;

use App\Entity\Caisse;
use App\Entity\User;
use App\Exception\SoldePiecesInsuffisantException;
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
        $caisses = $caisseRepository->trouverDisponibles();

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
        if (!$caisse->isStatutActif()
            || $caisse->getCollectionJeu() !== null
            && !$caisse->getCollectionJeu()->estDisponibleA(new \DateTimeImmutable())) {
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

        try {
            $stickman = $ouvertureCaisseService->ouvrir(
                $caisse,
                $utilisateur
            );
        } catch (SoldePiecesInsuffisantException $exception) {
            $this->addFlash('error', $exception->getMessage());

            return $this->redirectToRoute('app_caisse_publique');
        }

        if ($stickman === null) {
            $this->addFlash(
                'error',
                'Cette caisse ne contient aucun Stickman.'
            );
        } else {
            $this->addFlash(
                'success',
                sprintf(
                    'Tu as obtenu : %s. Solde restant : %d pièces.',
                    $stickman->getNom(),
                    $utilisateur->getPieces(),
                )
            );
        }

        return $this->redirectToRoute('app_caisse_publique');
    }
}
