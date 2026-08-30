<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ModifierPseudoType;
use App\Service\ModificationPseudoService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class PseudoController extends AbstractController
{
    #[Route(
        '/profil/pseudo',
        name: 'app_modifier_pseudo',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_USER')]
    public function modifier(
        Request $request,
        ModificationPseudoService $modificationPseudoService,
    ): Response {
        $joueur = $this->getUser();

        if (!$joueur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ModifierPseudoType::class, [
            'pseudo' => $joueur->getPseudo(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $resultat = $modificationPseudoService->modifier(
                $joueur,
                (string) $form->get('pseudo')->getData(),
            );

            if ($resultat === ModificationPseudoService::RESULTAT_OK) {
                $this->addFlash(
                    'success',
                    'Ton pseudo a bien été modifié.',
                );

                return $this->redirectToRoute('app_profil');
            }

            $form->get('pseudo')->addError(new FormError(
                $resultat === ModificationPseudoService::RESULTAT_IDENTIQUE
                    ? 'Ce pseudo est déjà le tien.'
                    : 'Ce pseudo est déjà utilisé.',
            ));
        }

        return $this->render('profil/modifier_pseudo.html.twig', [
            'form' => $form,
        ]);
    }
}
