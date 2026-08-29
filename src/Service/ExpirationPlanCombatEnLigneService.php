<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\PlanRoundCombat;
use App\Repository\CombatRepository;
use App\Repository\PlanRoundCombatRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\Clock\ClockInterface;

final class ExpirationPlanCombatEnLigneService
{
    public const DUREE_MAX_ATTENTE_PLAN_SECONDES = 300;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatRepository $combatRepository,
        private readonly PlanRoundCombatRepository $planRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function expirerSiNecessaire(int $combatId): bool
    {
        return $this->entityManager->wrapInTransaction(
            function () use ($combatId): bool {
                $combat = $this->combatRepository
                    ->trouverAvecVerrouEcriture($combatId);

                if (!$combat instanceof Combat) {
                    throw new LogicException(
                        'Le combat demandé est introuvable.'
                    );
                }

                if (!$combat->estEnCours()) {
                    return false;
                }

                $plans = $this->planRepository
                    ->trouverPourCombatEtRound(
                        $combat,
                        $combat->getNumeroRound(),
                    );

                if (count($plans) !== 1) {
                    return false;
                }

                $plan = $plans[0];

                if (!$plan instanceof PlanRoundCombat) {
                    throw new LogicException(
                        'Le plan enregistré est invalide.'
                    );
                }

                if ($this->clock->now() < $this->dateExpiration($plan)) {
                    return false;
                }

                $combat->setGagnant($plan->getJoueur());
                $combat->setStatut(Combat::STATUT_FORFAIT);

                return true;
            }
        );
    }

    public function dateExpiration(
        PlanRoundCombat $plan,
    ): DateTimeImmutable {
        return $plan->getDateSoumission()->modify(
            '+'.self::DUREE_MAX_ATTENTE_PLAN_SECONDES.' seconds'
        );
    }
}
