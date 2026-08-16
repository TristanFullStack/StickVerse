<?php

namespace App\Service;

use App\Model\EtatEquipeCombat;
use App\Model\PlanCombat;
use InvalidArgumentException;
use LogicException;

final class ResolutionRoundService
{
    public function __construct(
        private readonly CombatService $combatService,
    ) {
    }

    /**
     * @return array<string, array{
     *     attaque: int,
     *     defense: int,
     *     degatsCalcules: int,
     *     degatsEffectifs: int,
     *     overkill: int,
     *     pvAvant: int,
     *     pvRestants: int
     * }>
     */
    public function resoudre(
        EtatEquipeCombat $joueur1,
        PlanCombat $planJoueur1,
        EtatEquipeCombat $joueur2,
        PlanCombat $planJoueur2,
    ): array {
        $impacts = [];

        $this->ajouterImpacts(
            impacts: $impacts,
            prefixeCible: 'joueur2',
            attaquant: $joueur1,
            planAttaquant: $planJoueur1,
            defenseur: $joueur2,
            planDefenseur: $planJoueur2,
        );

        $this->ajouterImpacts(
            impacts: $impacts,
            prefixeCible: 'joueur1',
            attaquant: $joueur2,
            planAttaquant: $planJoueur2,
            defenseur: $joueur1,
            planDefenseur: $planJoueur1,
        );

        /*
         * Tous les impacts sont calculés avant la modification des PV.
         * La résolution reste donc simultanée.
         */
        $resultats = $this->combatService->resoudreRound($impacts);

        $this->appliquerResultats(
            resultats: $resultats,
            joueur1: $joueur1,
            joueur2: $joueur2,
        );

        return $resultats;
    }

    /**
     * @param array<string, array{
     *     attaquants: list<\App\Entity\Stickman>,
     *     defenseurs: list<\App\Entity\Stickman>,
     *     pvActuels: int
     * }> $impacts
     */
    private function ajouterImpacts(
        array &$impacts,
        string $prefixeCible,
        EtatEquipeCombat $attaquant,
        PlanCombat $planAttaquant,
        EtatEquipeCombat $defenseur,
        PlanCombat $planDefenseur,
    ): void {
        $ciblesAttaque = [
            'X' => $planAttaquant->getCibleAttaqueX(),
            'Y' => $planAttaquant->getCibleAttaqueY(),
        ];

        $attaquantsParCible = [];

        foreach ($ciblesAttaque as $groupe => $cible) {
            $stickmenAttaquants = $attaquant
                ->getStickmenVivantsDuGroupe($groupe);

            if ($stickmenAttaquants === []) {
                continue;
            }

            if (!$defenseur->estVivant($cible)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Le groupe %s ne peut pas attaquer le slot %s, car il est KO.',
                        $groupe,
                        $cible,
                    )
                );
            }

            $attaquantsParCible[$cible] ??= [];

            array_push(
                $attaquantsParCible[$cible],
                ...$stickmenAttaquants,
            );
        }

        $ciblesDefense = [
            'X' => $planDefenseur->getCibleDefenseX(),
            'Y' => $planDefenseur->getCibleDefenseY(),
        ];

        $defenseursParCible = [];

        foreach ($ciblesDefense as $groupe => $cible) {
            $stickmenDefenseurs = $defenseur
                ->getStickmenVivantsDuGroupe($groupe);

            if ($stickmenDefenseurs === []) {
                continue;
            }

            if (!$defenseur->estVivant($cible)) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Le groupe %s ne peut pas défendre le slot %s, car il est KO.',
                        $groupe,
                        $cible,
                    )
                );
            }

            $defenseursParCible[$cible] ??= [];

            array_push(
                $defenseursParCible[$cible],
                ...$stickmenDefenseurs,
            );
        }

        foreach ($attaquantsParCible as $cible => $stickmenAttaquants) {
            $impacts[$prefixeCible.'_'.$cible] = [
                'attaquants' => $stickmenAttaquants,
                'defenseurs' => $defenseursParCible[$cible] ?? [],
                'pvActuels' => $defenseur->getPvActuels($cible),
            ];
        }
    }

    /**
     * @param array<string, array{
     *     attaque: int,
     *     defense: int,
     *     degatsCalcules: int,
     *     degatsEffectifs: int,
     *     overkill: int,
     *     pvAvant: int,
     *     pvRestants: int
     * }> $resultats
     */
    private function appliquerResultats(
        array $resultats,
        EtatEquipeCombat $joueur1,
        EtatEquipeCombat $joueur2,
    ): void {
        foreach ($resultats as $cible => $resultat) {
            [$joueur, $slot] = explode('_', $cible, 2);

            $etatEquipe = match ($joueur) {
                'joueur1' => $joueur1,
                'joueur2' => $joueur2,
                default => throw new LogicException(
                    'Le propriétaire de la cible est inconnu.'
                ),
            };

            $etatEquipe->appliquerPvRestants(
                slot: $slot,
                pvRestants: $resultat['pvRestants'],
            );
        }
    }
}