<?php

namespace App\Controller;

use App\Entity\CombattantCombat;
use App\Entity\Combat;
use App\Entity\PlanRoundCombat;
use App\Entity\ResultatRoundCombat;
use App\Entity\User;
use App\Model\PlanCombat;
use App\Repository\CombattantCombatRepository;
use App\Repository\PlanRoundCombatRepository;
use App\Repository\ResultatRoundCombatRepository;
use App\Security\Voter\CombatVoter;
use App\Service\AbandonCombatService;
use App\Service\AnnulationCombatEnLigneService;
use App\Service\ExpirationCombatEnAttenteService;
use App\Service\ResolutionRoundCombatEnLigneService;
use App\Service\SoumissionPlanCombatService;
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
    '/combat-en-ligne',
    name: 'app_combat_en_ligne_'
)]
#[IsGranted('ROLE_USER')]
final class CombatEnLigneController extends AbstractController
{
    #[Route(
        '/{id}',
        name: 'etat',
        methods: ['GET']
    )]
    public function etat(
        Combat $combat,
        CombattantCombatRepository $combattantRepository,
        PlanRoundCombatRepository $planRepository,
        ResultatRoundCombatRepository $resultatRoundRepository,
        CsrfTokenManagerInterface $csrfTokenManager,
        ExpirationCombatEnAttenteService $expirationService,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(
            CombatVoter::CONSULTER,
            $combat,
        );

        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $combatId = $combat->getId();
        $expirationAutomatique = $combatId !== null
            && $expirationService->expirerSiNecessaire($combatId);

        $adversaire = $combat->getJoueur1() === $utilisateur
            ? $combat->getJoueur2()
            : $combat->getJoueur1();

        $plans = $planRepository
            ->trouverPourCombatEtRound(
                $combat,
                $combat->getNumeroRound(),
            );

        $planSoumis = false;
        $adversairePret = false;

        foreach ($plans as $plan) {
            if (!$plan instanceof PlanRoundCombat) {
                continue;
            }

            if ($plan->getJoueur() === $utilisateur) {
                $planSoumis = true;

                continue;
            }

            $adversairePret = true;
        }

        return $this->json([
            'combatId' => $combat->getId(),
            'statut' => $combat->getStatut(),
            'expirationAutomatique' => $expirationAutomatique,
            'numeroRound' => $combat->getNumeroRound(),
            'gagnantId' => $combat->getGagnant()?->getId(),
            'dernierRound' => $combat->getDernierRoundResolu() !== null
                ? [
                    'numero' => $combat->getDernierRoundResolu(),
                    'positionMoi' => $combat->getJoueur1() === $utilisateur
                        ? 'joueur1'
                        : 'joueur2',
                    'resultats' => $combat->getDerniersResultats(),
                ]
                : null,
            'historiqueRounds' => array_map(
                static fn (
                    ResultatRoundCombat $resultatRound
                ): array => [
                    'numero' => $resultatRound->getNumeroRound(),
                    'resultats' => $resultatRound->getResultats(),
                ],
                $resultatRoundRepository
                    ->trouverPourCombat($combat),
            ),
            'moi' => $this->serialiserParticipant(
                $combat,
                $utilisateur,
                $combattantRepository,
            ),
            'adversaire' => $adversaire instanceof User
                ? $this->serialiserParticipant(
                    $combat,
                    $adversaire,
                    $combattantRepository,
                )
                : null,
            'planSoumis' => $planSoumis,
            'adversairePret' => $adversairePret,
            'csrf' => [
                'plan' => $csrfTokenManager
                    ->getToken(
                        $this->creerIdentifiantCsrf(
                            'plan',
                            $combat,
                        )
                    )
                    ->getValue(),
                'abandon' => $csrfTokenManager
                    ->getToken(
                        $this->creerIdentifiantCsrf(
                            'abandon',
                            $combat,
                        )
                    )
                    ->getValue(),
                'annuler' => $csrfTokenManager
                    ->getToken(
                        $this->creerIdentifiantCsrf(
                            'annuler',
                            $combat,
                        )
                    )
                    ->getValue(),
            ],
        ]);
    }

    /**
     * @return array{
     *     id: int|null,
     *     email: string|null,
     *     combattants: list<array{
     *         slot: string,
     *         stickmanIdOriginal: int,
     *         nom: string,
     *         image: string,
     *         rarete: int,
     *         pvMaximum: int,
     *         pvActuels: int,
     *         attaque: int,
     *         defense: int,
     *         vivant: bool
     *     }>
     * }
     */
    private function serialiserParticipant(
        Combat $combat,
        User $joueur,
        CombattantCombatRepository $combattantRepository,
    ): array {
        return [
            'id' => $joueur->getId(),
            'email' => $joueur->getEmail(),
            'combattants' => array_map(
                static fn (CombattantCombat $combattant): array => [
                    'slot' => $combattant->getSlot(),
                    'stickmanIdOriginal' => $combattant->getStickmanIdOriginal(),
                    'nom' => $combattant->getNomSnapshot(),
                    'image' => $combattant->getImageSnapshot(),
                    'rarete' => $combattant->getRareteSnapshot(),
                    'pvMaximum' => $combattant->getPvMaximum(),
                    'pvActuels' => $combattant->getPvActuels(),
                    'attaque' => $combattant->getAttaqueSnapshot(),
                    'defense' => $combattant->getDefenseSnapshot(),
                    'vivant' => $combattant->estVivant(),
                ],
                $combattantRepository->trouverPourCombatEtJoueur(
                    $combat,
                    $joueur,
                ),
            ),
        ];
    }

    #[Route(
        '/{id}/plan',
        name: 'plan',
        methods: ['POST']
    )]
    public function soumettrePlan(
        Combat $combat,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        SoumissionPlanCombatService $soumissionService,
        ResolutionRoundCombatEnLigneService $resolutionService,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(
            CombatVoter::JOUER,
            $combat,
        );

        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (
            !$this->csrfEstValide(
                'plan',
                $combat,
                $request,
                $csrfTokenManager,
            )
        ) {
            return $this->json(
                [
                    'erreur' => 'Le jeton CSRF du plan est invalide.',
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
            $plan = $this->creerPlanCombat($donnees);
        } catch (InvalidArgumentException $exception) {
            return $this->json(
                [
                    'erreur' => $exception->getMessage(),
                ],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $combatId = $combat->getId();

        if ($combatId === null) {
            return $this->json(
                [
                    'erreur' => 'Le combat demandé est introuvable.',
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        try {
            $soumissionService->soumettre(
                $combatId,
                $utilisateur,
                $plan,
            );

            $resultats = $resolutionService
                ->resoudreSiPret($combatId);
        } catch (LogicException $exception) {
            return $this->json(
                [
                    'erreur' => $exception->getMessage(),
                ],
                Response::HTTP_CONFLICT,
            );
        }

        if ($resultats === null) {
            return $this->json(
                [
                    'etat' => 'en_attente_adversaire',
                    'combatId' => $combatId,
                    'statut' => $combat->getStatut(),
                    'numeroRound' => $combat->getNumeroRound(),
                ],
                Response::HTTP_CREATED,
            );
        }

        return $this->json([
            'etat' => $combat->estEnCours()
                ? 'round_resolu'
                : 'combat_termine',
            'combatId' => $combatId,
            'statut' => $combat->getStatut(),
            'numeroRound' => $combat->getNumeroRound(),
            'gagnantId' => $combat->getGagnant()?->getId(),
            'resultats' => $resultats,
        ]);
    }

    #[Route(
        '/{id}/abandon',
        name: 'abandon',
        methods: ['POST']
    )]
    public function abandonner(
        Combat $combat,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        AbandonCombatService $abandonService,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(
            CombatVoter::JOUER,
            $combat,
        );

        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (
            !$this->csrfEstValide(
                'abandon',
                $combat,
                $request,
                $csrfTokenManager,
            )
        ) {
            return $this->json(
                [
                    'erreur' => 'Le jeton CSRF d’abandon est invalide.',
                ],
                Response::HTTP_FORBIDDEN,
            );
        }

        $combatId = $combat->getId();

        if ($combatId === null) {
            return $this->json(
                [
                    'erreur' => 'Le combat demandé est introuvable.',
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        try {
            $combatAbandonne = $abandonService->abandonner(
                $combatId,
                $utilisateur,
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
            'etat' => 'combat_abandonne',
            'combatId' => $combatId,
            'statut' => $combatAbandonne->getStatut(),
            'numeroRound' => $combatAbandonne->getNumeroRound(),
            'gagnantId' => $combatAbandonne
                ->getGagnant()
                ?->getId(),
        ]);
    }

    #[Route(
        '/{id}/annuler',
        name: 'annuler',
        methods: ['POST']
    )]
    public function annuler(
        Combat $combat,
        Request $request,
        CsrfTokenManagerInterface $csrfTokenManager,
        AnnulationCombatEnLigneService $annulationService,
    ): JsonResponse {
        $this->denyAccessUnlessGranted(
            CombatVoter::JOUER,
            $combat,
        );

        $utilisateur = $this->getUser();

        if (!$utilisateur instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if (
            !$this->csrfEstValide(
                'annuler',
                $combat,
                $request,
                $csrfTokenManager,
            )
        ) {
            return $this->json(
                [
                    'erreur' => 'Le jeton CSRF d’annulation est invalide.',
                ],
                Response::HTTP_FORBIDDEN,
            );
        }

        $combatId = $combat->getId();

        if ($combatId === null) {
            return $this->json(
                [
                    'erreur' => 'Le combat demandé est introuvable.',
                ],
                Response::HTTP_NOT_FOUND,
            );
        }

        try {
            $combatAnnule = $annulationService->annuler(
                $combatId,
                $utilisateur,
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
            'etat' => 'combat_annule',
            'combatId' => $combatId,
            'statut' => $combatAnnule->getStatut(),
            'numeroRound' => $combatAnnule->getNumeroRound(),
            'gagnantId' => null,
        ]);
    }

    /**
     * @param array<string, mixed> $donnees
     */
    private function creerPlanCombat(
        array $donnees,
    ): PlanCombat {
        $cibleAttaqueX = $this->lireCible(
            $donnees,
            'cibleAttaqueX',
        );

        $cibleAttaqueY = $this->lireCible(
            $donnees,
            'cibleAttaqueY',
        );

        $cibleDefenseX = $this->lireCible(
            $donnees,
            'cibleDefenseX',
        );

        $cibleDefenseY = $this->lireCible(
            $donnees,
            'cibleDefenseY',
        );

        return new PlanCombat(
            $cibleAttaqueX,
            $cibleAttaqueY,
            $cibleDefenseX,
            $cibleDefenseY,
        );
    }

    /**
     * @param array<string, mixed> $donnees
     */
    private function lireCible(
        array $donnees,
        string $cle,
    ): string {
        $cible = $donnees[$cle] ?? null;

        if (!is_string($cible)) {
            throw new InvalidArgumentException(
                'Les quatre cibles du plan sont obligatoires.'
            );
        }

        return strtoupper(trim($cible));
    }

    private function csrfEstValide(
        string $action,
        Combat $combat,
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
                    $action,
                    $combat,
                ),
                $valeurJeton,
            )
        );
    }

    private function creerIdentifiantCsrf(
        string $action,
        Combat $combat,
    ): string {
        $combatId = $combat->getId();

        if ($combatId === null) {
            throw new LogicException(
                'Le combat doit être enregistré.'
            );
        }

        return 'combat_'.$action.'_'.$combatId;
    }
}
