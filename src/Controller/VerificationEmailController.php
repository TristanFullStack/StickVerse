<?php

namespace App\Controller;

use App\Service\VerificationEmailService;
use App\Service\LimitationActionsSensiblesService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class VerificationEmailController extends AbstractController
{
    #[Route(
        '/verification-email/{jeton}',
        name: 'app_verification_email',
        requirements: ['jeton' => '[A-Fa-f0-9]{64}'],
        methods: ['GET'],
    )]
    public function verifier(
        string $jeton,
        VerificationEmailService $verificationEmailService,
        Security $security,
        EntityManagerInterface $entityManager,
    ): Response {
        $utilisateur = $verificationEmailService->confirmer($jeton);

        if ($utilisateur === null) {
            return $this->render(
                'security/verification_email.html.twig',
                ['valide' => false],
                new Response(status: Response::HTTP_BAD_REQUEST),
            );
        }

        $connexionAutomatique = $utilisateur->doitSeConnecterAutomatiquementApresVerification();
        // L’intention ne doit être utilisable qu’une seule fois, même si le
        // lien de confirmation est ouvert dans plusieurs onglets.
        $utilisateur->setConnexionAutomatiqueApresVerification(false);
        $entityManager->flush();

        if ($connexionAutomatique) {
            $security->login($utilisateur);
            $this->addFlash(
                'success',
                'Ton adresse e-mail est confirmée. Bienvenue sur StickVerse !',
            );

            return $this->redirectToRoute('app_home');
        }

        $this->addFlash(
            'success',
            'Ton adresse e-mail est confirmée. Tu peux maintenant te connecter.',
        );

        return $this->redirectToRoute('app_login', [
            '_username' => $utilisateur->getEmail(),
        ]);
    }

    #[Route(
        '/verification-email/renvoyer',
        name: 'app_verification_email_renvoyer',
        methods: ['POST'],
    )]
    public function renvoyer(
        Request $request,
        VerificationEmailService $verificationEmailService,
        LimitationActionsSensiblesService $limitationService,
    ): Response {
        if (!$this->isCsrfTokenValid(
            'verification-email-renvoi',
            $request->getPayload()->getString('_token'),
        )) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $email = trim($request->getPayload()->getString('email'));
        if (
            filter_var($email, FILTER_VALIDATE_EMAIL) !== false
            && $limitationService->consommerPourIdentifiant(
                $email,
                'verification_email',
                $request->getClientIp(),
            ) === null
        ) {
            $verificationEmailService->renvoyer($email);
        }

        $this->addFlash(
            'success',
            'Si cette adresse correspond à un compte non confirmé, un nouvel e-mail vient d’être envoyé.',
        );

        return $this->redirectToRoute('app_login');
    }
}
