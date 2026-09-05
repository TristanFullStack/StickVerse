<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\SupprimerCompteType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class CompteController extends AbstractController
{
    #[Route(
        '/profil/supprimer',
        name: 'app_supprimer_compte',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_USER')]
    public function supprimer(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $joueur = $this->getUser();
        if (!$joueur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(SupprimerCompteType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $motDePasse = (string) $form->get('motDePasse')->getData();
            if (!$passwordHasher->isPasswordValid($joueur, $motDePasse)) {
                $form->get('motDePasse')->addError(
                    new FormError('Le mot de passe actuel est incorrect.'),
                );
            } else {
                $entityManager->remove($joueur);
                $entityManager->flush();
                $request->getSession()->invalidate();

                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('compte/supprimer.html.twig', [
            'form' => $form,
        ]);
    }
}
