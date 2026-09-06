<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\InitialisationNouveauJoueurService;
use App\Service\VerificationEmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        InitialisationNouveauJoueurService $initialisationNouveauJoueur,
        VerificationEmailService $verificationEmailService,
    ): Response {
        $user = (new User())->setPseudo('');
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));
            $user->setEmailVerifie(false);
            $user->setConnexionAutomatiqueApresVerification(
                (bool) $form->get('connexionAutomatique')->getData(),
            );

            $entityManager->persist($user);
            $initialisationNouveauJoueur->initialiser($user);
            $entityManager->flush();
            $verificationEmailService->envoyer($user);

            $this->addFlash(
                'success',
                'Compte créé ! Consulte ton adresse e-mail pour confirmer ton compte avant de te connecter.',
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
