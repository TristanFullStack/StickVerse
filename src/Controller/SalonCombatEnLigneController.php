<?php

namespace App\Controller;

use App\Entity\Combat;
use App\Entity\Equipe;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Repository\EquipeRepository;
use App\Service\CreationCombatEnLigneService;
use App\Service\RejoindreCombatEnLigneService;
use InvalidArgumentException;
use JsonException;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(
    '/salon-combat-en-ligne',
    name: 'app_salon_combat_en_ligne_'
)]
#[IsGranted('ROLE_USER')]
final class SalonCombatEnLigneController extends AbstractController
{
    #[Route(
        '',
        name: 'etat',
        methods: ['GET']
    )]
    public function etat(
        CombatRepository $combatRepository,
        EquipeRepository $equipeRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): JsonResponse {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $combatActif = $combatRepository
            ->trouverActifPourJoueur($utilisateur);

        $combatsDisponibles = $combatRepository
            ->trouverDisponiblesPour($utilisateur);

        $equipes = $equipeRepository->findBy(
            [
                'utilisateur' => $utilisateur,
            ],
            [
                'id' => 'ASC',
            ],
        );

        return $this->json([
            'combatActifId' => $combatActif?->getId(),
            'equipes' => array_map(
                static fn (
                    Equipe $equipe,
                ): array => [
                    'id' => $equipe->getId(),
                    'nom' => $equipe->getNom(),
                ],
                $equipes,
            ),
            'combatsDisponibles' => array_map(
                static fn (
                    Combat $combat,
                ): array => [
                    'id' => $combat->getId(),
                    'joueur1Id' => $combat
                        ->getJoueur1()
                        ->getId(),
                    'statut' => $combat->getStatut(),
                    'numeroRound' => $combat
                        ->getNumeroRound(),
                    'dateCreation' => $combat
                        ->getDateCreation()
                        ->format(DATE_ATOM),
                ],
                $combatsDisponibles,
            ),
            'csrf' => [
                'creer' => $csrfTokenManager
                    ->getToken(
                        $this->creerIdentifiantCsrf(
                            'creer'
                        )
                    )
                    ->getValue(),
                'rejoindre' => $csrfTokenManager
                    ->getToken(
                        $this->creerIdentifiantCsrf(
                            'rejoindre'
                        )
                    )
                    ->getValue(),
            ],
        ]);
    }

    #[Route(
        '/creer',
        name: 'creer',
        methods: ['POST']
    )]
    public function creer(
        Request $request,
        EquipeRepository $equipeRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
        CreationCombatEnLigneService $creationService,
    ): JsonResponse {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (
            !$this->csrfEstValide(
                'creer',
                $request,
                $csrfTokenManager,
            )
        ) {
            return $this->json(
                [
                    'erreur' => 'Le jeton CSRF de création est invalide.',
                ],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $donnees = $request->toArray();
        } catch (JsonException) {
            return $this->json(
                [
                    'erreur' => 'Le contenu JSON est invalide.',
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $equipeId = $this->lireEquipeId($donnees);
        } catch (InvalidArgumentException $exception) {
            return $this->json(
                [
                    'erreur' => $exception->getMessage(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $equipe = $equipeRepository->findOneBy([
            'id' => $equipeId,
            'utilisateur' => $utilisateur,
        ]);

        if (!$equipe instanceof Equipe) {
            return $this->json(
                [
                    'erreur' => 'L’équipe demandée est introuvable.',
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        try {
            $combat = $creationService->creer(
                $utilisateur,
                $equipe,
            );
        } catch (LogicException $exception) {
            return $this->json(
                [
                    'erreur' => $exception->getMessage(),
                ],
                Response::HTTP_CONFLICT,
            );
        }

        return $this->json(
            [
                'etat' => 'combat_cree',
                'combatId' => $combat->getId(),
                'statut' => $combat->getStatut(),
                'numeroRound' => $combat
                    ->getNumeroRound(),
            ],
            Response::HTTP_CREATED,
        );
    }

    #[Route(
        '/{id}/rejoindre',
        name: 'rejoindre',
        methods: ['POST']
    )]
    public function rejoindre(
        int $id,
        Request $request,
        EquipeRepository $equipeRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
        RejoindreCombatEnLigneService $rejoindreService,
    ): JsonResponse {
        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (
            !$this->csrfEstValide(
                'rejoindre',
                $request,
                $csrfTokenManager,
            )
        ) {
            return $this->json(
                [
                    'erreur' => 'Le jeton CSRF de jonction est invalide.',
                ],
                Response::HTTP_FORBIDDEN,
            );
        }

        try {
            $donnees = $request->toArray();
        } catch (JsonException) {
            return $this->json(
                [
                    'erreur' => 'Le contenu JSON est invalide.',
                ],
                Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $equipeId = $this->lireEquipeId($donnees);
        } catch (InvalidArgumentException $exception) {
            return $this->json(
                [
                    'erreur' => $exception->getMessage(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $equipe = $equipeRepository->findOneBy([
            'id' => $equipeId,
            'utilisateur' => $utilisateur,
        ]);

        if (!$equipe instanceof Equipe) {
            return $this->json(
                [
                    'erreur' => 'L’équipe demandée est introuvable.',
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        try {
            $combat = $rejoindreService->rejoindre(
                $id,
                $utilisateur,
                $equipe,
            );
        } catch (LogicException $exception) {
            return $this->json(
                [
                    'erreur' => $exception->getMessage(),
                ],
                Response::HTTP_CONFLICT,
            );
        }

        return $this->json([
            'etat' => 'combat_rejoint',
            'combatId' => $combat->getId(),
            'statut' => $combat->getStatut(),
            'numeroRound' => $combat->getNumeroRound(),
        ]);
    }

    /**
     * @param array<string, mixed> $donnees
     */
    private function lireEquipeId(
        array $donnees,
    ): int {
        $equipeId = $donnees['equipeId'] ?? null;

        if (
            !is_int($equipeId)
            || $equipeId <= 0
        ) {
            throw new InvalidArgumentException(
                'L’identifiant de l’équipe est invalide.'
            );
        }

        return $equipeId;
    }

    private function csrfEstValide(
        string $action,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
    ): bool {
        $valeurJeton = $request->headers->get(
            'X-CSRF-TOKEN'
        );

        if (!is_string($valeurJeton)) {
            return false;
        }

        return $csrfTokenManager->isTokenValid(
            new CsrfToken(
                $this->creerIdentifiantCsrf(
                    $action
                ),
                $valeurJeton,
            )
        );
    }

    private function creerIdentifiantCsrf(
        string $action,
    ): string {
        return 'salon_combat_'.$action;
    }
}