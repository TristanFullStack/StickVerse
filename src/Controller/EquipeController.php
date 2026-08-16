<?php

namespace App\Controller;

use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Entity\User;
use App\Form\EquipeType;
use App\Repository\EquipeRepository;
use App\Repository\InventaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class EquipeController extends AbstractController
{
    #[Route('/equipe', name: 'app_equipe', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        InventaireRepository $inventaireRepository,
        EquipeRepository $equipeRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $inventaires = $inventaireRepository->findBy([
            'utilisateur' => $utilisateur,
        ]);

        $stickmenDisponibles = [];

        foreach ($inventaires as $inventaire) {
            $stickman = $inventaire->getStickman();

            if ($stickman !== null) {
                $stickmenDisponibles[] = $stickman;
            }
        }

        $equipe = $equipeRepository->findOneBy([
            'utilisateur' => $utilisateur,
        ]);

        if ($equipe === null) {
            $equipe = new Equipe();
            $equipe->setUtilisateur($utilisateur);
        }

        $form = $this->createForm(EquipeType::class, $equipe, [
            'stickmen_disponibles' => $stickmenDisponibles,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $stickmenChoisis = [
                $equipe->getStickmanA(),
                $equipe->getStickmanB(),
                $equipe->getStickmanC(),
                $equipe->getStickmanD(),
            ];

            $identifiants = array_map(
                static fn (?Stickman $stickman): ?int => $stickman?->getId(),
                $stickmenChoisis,
            );

            if (count(array_unique($identifiants)) !== 4) {
                $form->addError(new FormError(
                    'Chaque emplacement doit contenir un Stickman différent.'
                ));
            } else {
                $entityManager->persist($equipe);
                $entityManager->flush();

                $this->addFlash('success', 'Ton équipe a bien été sauvegardée.');

                return $this->redirectToRoute('app_equipe');
            }
        }

        return $this->render('equipe/index.html.twig', [
            'form' => $form,
            'equipe' => $equipe,
            'nombre_stickmen_disponibles' => count($stickmenDisponibles),
        ]);
    }
}