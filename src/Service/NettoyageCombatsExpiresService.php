<?php

namespace App\Service;

use App\Repository\CombatRepository;

final class NettoyageCombatsExpiresService
{
    public function __construct(
        private readonly CombatRepository $combatRepository,
        private readonly ExpirationCombatEnAttenteService $expirationAttenteService,
        private readonly ExpirationPreparationCombatEnLigneService $expirationPreparationService,
        private readonly ExpirationPlanCombatEnLigneService $expirationPlanService,
    ) {
    }

    /**
     * @return array{
     *     examines: int,
     *     annulesAttente: int,
     *     annulesPreparation: int,
     *     forfaitsPreparation: int,
     *     forfaitsPlan: int
     * }
     */
    public function nettoyer(): array
    {
        $totaux = [
            'examines' => 0,
            'annulesAttente' => 0,
            'annulesPreparation' => 0,
            'forfaitsPreparation' => 0,
            'forfaitsPlan' => 0,
        ];

        foreach ($this->combatRepository->trouverIdsActifs() as $combatId) {
            $totaux['examines']++;

            if (
                $this->expirationAttenteService
                    ->expirerSiNecessaire($combatId)
            ) {
                $totaux['annulesAttente']++;

                continue;
            }

            $expirationPreparation =
                $this->expirationPreparationService
                    ->expirerSiNecessaire($combatId);

            if (
                $expirationPreparation
                === ExpirationPreparationCombatEnLigneService::RESULTAT_ANNULE
            ) {
                $totaux['annulesPreparation']++;

                continue;
            }

            if (
                $expirationPreparation
                === ExpirationPreparationCombatEnLigneService::RESULTAT_FORFAIT
            ) {
                $totaux['forfaitsPreparation']++;

                continue;
            }

            if (
                $this->expirationPlanService
                    ->expirerSiNecessaire($combatId)
            ) {
                $totaux['forfaitsPlan']++;
            }
        }

        return $totaux;
    }
}
