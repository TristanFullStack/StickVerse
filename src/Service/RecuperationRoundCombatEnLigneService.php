<?php

namespace App\Service;

use App\Entity\Combat;
use App\Repository\PlanRoundCombatRepository;

final class RecuperationRoundCombatEnLigneService
{
    public function __construct(
        private readonly PlanRoundCombatRepository $planRepository,
    ) {
    }

    public function doitRecuperer(Combat $combat): bool
    {
        $combatId = $combat->getId();

        if (
            $combatId === null
            || !$combat->estEnCours()
            || !$combat->estPretAJouer()
        ) {
            return false;
        }

        $plans = $this->planRepository
            ->trouverPourCombatEtRound(
                $combat,
                $combat->getNumeroRound(),
            );

        return count($plans) >= 2;
    }
}
