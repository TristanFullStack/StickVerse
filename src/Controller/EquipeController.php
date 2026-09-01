<?php

namespace App\Controller;

use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Entity\User;
use App\Form\EquipeType;
use App\Repository\EquipeRepository;
use App\Repository\InventaireRepository;
use App\Service\ScorePuissanceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
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
        ScorePuissanceService $scorePuissanceService,
    ): Response {
        $utilisateur = $this->utilisateurConnecte();
        $stickmenDisponibles = $this->stickmenDisponibles(
            $inventaireRepository,
            $utilisateur,
        );
        $equipe = (new Equipe())->setUtilisateur($utilisateur);

        $form = $this->createForm(EquipeType::class, $equipe, [
            'stickmen_disponibles' => $stickmenDisponibles,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->validerEquipe($form, $equipe, $equipeRepository);

            if ($form->isValid()) {
                $entityManager->persist($equipe);
                $entityManager->flush();

                $this->addFlash('success', 'La nouvelle équipe a bien été créée.');

                return $this->redirectToRoute('app_equipe');
            }
        }

        $equipes = $equipeRepository->findBy(
            ['utilisateur' => $utilisateur],
            ['id' => 'ASC'],
        );
        $puissances = [];

        foreach ($equipes as $equipeExistante) {
            if ($equipeExistante->getId() !== null) {
                $puissances[$equipeExistante->getId()] =
                    $scorePuissanceService->calculerEquipe($equipeExistante);
            }
        }

        return $this->render('equipe/index.html.twig', [
            'form' => $form,
            'equipes' => $equipes,
            'puissances' => $puissances,
            'nombre_stickmen_disponibles' => count($stickmenDisponibles),
        ]);
    }

    #[Route('/equipe/{id}/modifier', name: 'app_equipe_modifier', methods: ['GET', 'POST'])]
    public function modifier(
        Equipe $equipe,
        Request $request,
        InventaireRepository $inventaireRepository,
        EquipeRepository $equipeRepository,
        EntityManagerInterface $entityManager,
        ScorePuissanceService $scorePuissanceService,
    ): Response {
        $utilisateur = $this->utilisateurConnecte();
        $this->verifierProprietaire($equipe, $utilisateur);
        $stickmenDisponibles = $this->stickmenDisponibles(
            $inventaireRepository,
            $utilisateur,
        );
        $form = $this->createForm(EquipeType::class, $equipe, [
            'stickmen_disponibles' => $stickmenDisponibles,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->validerEquipe($form, $equipe, $equipeRepository);

            if ($form->isValid()) {
                $entityManager->flush();
                $this->addFlash('success', 'L’équipe a bien été modifiée.');

                return $this->redirectToRoute('app_equipe');
            }
        }

        return $this->render('equipe/modifier.html.twig', [
            'form' => $form,
            'equipe' => $equipe,
            'puissance' => $scorePuissanceService->calculerEquipe($equipe),
            'nombre_stickmen_disponibles' => count($stickmenDisponibles),
        ]);
    }

    #[Route('/equipe/{id}/supprimer', name: 'app_equipe_supprimer', methods: ['POST'])]
    public function supprimer(
        Equipe $equipe,
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $utilisateur = $this->utilisateurConnecte();
        $this->verifierProprietaire($equipe, $utilisateur);

        if (!$this->isCsrfTokenValid(
            'supprimer-equipe-'.$equipe->getId(),
            (string) $request->request->get('_token'),
        )) {
            throw $this->createAccessDeniedException(
                'Le jeton de suppression est invalide.'
            );
        }

        $entityManager->remove($equipe);
        $entityManager->flush();
        $this->addFlash('success', 'L’équipe a bien été supprimée.');

        return $this->redirectToRoute('app_equipe');
    }

    private function utilisateurConnecte(): User
    {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $utilisateur;
    }

    /**
     * @return list<Stickman>
     */
    private function stickmenDisponibles(
        InventaireRepository $inventaireRepository,
        User $utilisateur,
    ): array {
        $stickmen = [];

        foreach ($inventaireRepository->findBy(['utilisateur' => $utilisateur]) as $inventaire) {
            $stickman = $inventaire->getStickman();

            if ($stickman?->getId() !== null) {
                $stickmen[$stickman->getId()] = $stickman;
            }
        }

        return array_values($stickmen);
    }

    private function validerEquipe(
        FormInterface $form,
        Equipe $equipe,
        EquipeRepository $equipeRepository,
    ): void {
        $identifiants = array_map(
            static fn (?Stickman $stickman): ?int => $stickman?->getId(),
            [
                $equipe->getStickmanA(),
                $equipe->getStickmanB(),
                $equipe->getStickmanC(),
                $equipe->getStickmanD(),
            ],
        );

        if (count(array_unique($identifiants)) !== 4) {
            $form->addError(new FormError(
                'Chaque emplacement doit contenir un Stickman différent.'
            ));
        }

        $utilisateur = $equipe->getUtilisateur();
        $nom = $equipe->getNom();

        if (
            $utilisateur instanceof User
            && is_string($nom)
            && $equipeRepository->nomExistePourUtilisateur(
                $utilisateur,
                $nom,
                $equipe,
            )
        ) {
            $form->get('nom')->addError(new FormError(
                'Tu possèdes déjà une équipe portant ce nom.'
            ));
        }
    }

    private function verifierProprietaire(
        Equipe $equipe,
        User $utilisateur,
    ): void {
        $proprietaire = $equipe->getUtilisateur();

        if (
            $proprietaire === $utilisateur
            || (
                $proprietaire?->getId() !== null
                && $proprietaire->getId() === $utilisateur->getId()
            )
        ) {
            return;
        }

        throw $this->createAccessDeniedException(
            'Cette équipe ne t’appartient pas.'
        );
    }
}
