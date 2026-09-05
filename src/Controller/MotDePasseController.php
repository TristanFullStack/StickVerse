<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\DemandeReinitialisationMotDePasseType;
use App\Form\ModifierMotDePasseType;
use App\Form\ReinitialiserMotDePasseType;
use App\Service\ModificationMotDePasseService;
use App\Service\LimitationActionsSensiblesService;
use App\Service\RecuperationMotDePasseService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class MotDePasseController extends AbstractController
{
    #[Route(
        '/profil/mot-de-passe',
        name: 'app_modifier_mot_de_passe',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_USER')]
    public function modifier(
        Request $request,
        ModificationMotDePasseService $modificationMotDePasseService,
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ModifierMotDePasseType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $motDePasseActuel = (string) $form
                ->get('motDePasseActuel')
                ->getData();
            $nouveauMotDePasse = (string) $form
                ->get('nouveauMotDePasse')
                ->getData();

            $resultat = $modificationMotDePasseService->modifier(
                $utilisateur,
                $motDePasseActuel,
                $nouveauMotDePasse,
            );

            if (
                $resultat === ModificationMotDePasseService::RESULTAT_OK
            ) {
                $this->addFlash(
                    'success',
                    'Ton mot de passe a bien été modifié.',
                );

                return $this->redirectToRoute('app_profil');
            }

            $message = $resultat
                === ModificationMotDePasseService::RESULTAT_IDENTIQUE
                ? 'Le nouveau mot de passe doit être différent de l’ancien.'
                : 'Le mot de passe actuel est incorrect.';

            $form->get('motDePasseActuel')->addError(
                new FormError($message)
            );
        }

        return $this->render('mot_de_passe/modifier.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(
        '/mot-de-passe/oublie',
        name: 'app_demande_reinitialisation_mot_de_passe',
        methods: ['GET', 'POST'],
    )]
    public function demanderReinitialisation(
        Request $request,
        RecuperationMotDePasseService $recuperationMotDePasseService,
        LimitationActionsSensiblesService $limitationService,
    ): Response {
        $form = $this->createForm(
            DemandeReinitialisationMotDePasseType::class
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = (string) $form->get('email')->getData();
            if ($limitationService->consommerPourIdentifiant(
                $email,
                'demande_reinitialisation',
                $request->getClientIp(),
            ) === null) {
                $recuperationMotDePasseService->demander($email);
            }

            $this->addFlash(
                'success',
                'Si cette adresse correspond à un compte, un lien de réinitialisation vient d’être envoyé.',
            );

            return $this->redirectToRoute(
                'app_demande_reinitialisation_mot_de_passe'
            );
        }

        return $this->render('mot_de_passe/demander.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(
        '/mot-de-passe/reinitialiser/{jeton}',
        name: 'app_reinitialiser_mot_de_passe',
        methods: ['GET', 'POST'],
    )]
    public function reinitialiser(
        string $jeton,
        Request $request,
        RecuperationMotDePasseService $recuperationMotDePasseService,
    ): Response {
        $demande = $recuperationMotDePasseService
            ->trouverDemandeValide($jeton);

        if ($demande === null) {
            return $this->render(
                'mot_de_passe/jeton_invalide.html.twig',
                [],
                new Response(status: Response::HTTP_BAD_REQUEST),
            );
        }

        $form = $this->createForm(ReinitialiserMotDePasseType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $recuperationMotDePasseService->reinitialiser(
                $demande,
                (string) $form->get('nouveauMotDePasse')->getData(),
            );

            $this->addFlash(
                'success',
                'Ton mot de passe a été réinitialisé. Tu peux maintenant te connecter.',
            );

            return $this->redirectToRoute('app_login');
        }

        return $this->render('mot_de_passe/reinitialiser.html.twig', [
            'form' => $form,
        ]);
    }
}
