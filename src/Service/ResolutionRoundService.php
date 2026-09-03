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
        int $numeroRound = 1,
    ): array {
        $impacts = [];

        $this->ajouterImpacts(
            impacts: $impacts,
            prefixeCible: 'joueur2',
            attaquant: $joueur1,
            planAttaquant: $planJoueur1,
            defenseur: $joueur2,
            planDefenseur: $planJoueur2,
            numeroRound: $numeroRound,
        );

        $this->ajouterImpacts(
            impacts: $impacts,
            prefixeCible: 'joueur1',
            attaquant: $joueur2,
            planAttaquant: $planJoueur2,
            defenseur: $joueur1,
            planDefenseur: $planJoueur1,
            numeroRound: $numeroRound,
        );

        /*
         * Tous les impacts sont calculés avant la modification des PV.
         * La résolution reste donc simultanée.
         */
        $resultats = $this->combatService->resoudreRound(
            $impacts,
            $numeroRound,
        );

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
     *     pvActuels: int,
     *     contexte: array<string, mixed>
     * }> $impacts
     */
    private function ajouterImpacts(
        array &$impacts,
        string $prefixeCible,
        EtatEquipeCombat $attaquant,
        PlanCombat $planAttaquant,
        EtatEquipeCombat $defenseur,
        PlanCombat $planDefenseur,
        int $numeroRound,
    ): void {
        $ciblesAttaque = [
            'X' => $planAttaquant->getCibleAttaqueX(),
            'Y' => $planAttaquant->getCibleAttaqueY(),
        ];

        $attaquantsParCible = [];
        $groupesAttaquantsParCible = [];

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
            $groupesAttaquantsParCible[$cible][] = $groupe;
        }

        $ciblesDefense = [
            'X' => $planDefenseur->getCibleDefenseX(),
            'Y' => $planDefenseur->getCibleDefenseY(),
        ];

        $defenseursParCible = [];
        $groupesDefenseParCible = [];

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
            $groupesDefenseParCible[$cible][] = $groupe;
        }

        foreach ($attaquantsParCible as $cible => $stickmenAttaquants) {
            $groupesAttaquants = array_values(array_unique($groupesAttaquantsParCible[$cible] ?? []));
            $groupesDefense = array_values(array_unique($groupesDefenseParCible[$cible] ?? []));
            $acteursAttaquants = [];
            foreach ($groupesAttaquants as $groupe) {
                $acteursAttaquants = array_merge(
                    $acteursAttaquants,
                    $this->construireActeurs($attaquant, $groupe),
                );
            }
            $acteursDefenseurs = [];
            foreach ($groupesDefense as $groupe) {
                $acteursDefenseurs = array_merge(
                    $acteursDefenseurs,
                    $this->construireActeurs($defenseur, $groupe, $cible),
                );
            }

            $impacts[$prefixeCible.'_'.$cible] = [
                'attaquants' => $stickmenAttaquants,
                'defenseurs' => $defenseursParCible[$cible] ?? [],
                'pvActuels' => $defenseur->getPvActuels($cible),
                'contexte' => [
                    'modeAttaque' => $planAttaquant->estFocus() ? 'focus' : 'split',
                    'doubleDefense' => $planDefenseur->estDoubleDefense() && count($groupesDefense) > 1,
                    'premiereDefense' => $numeroRound === 1,
                    'equipesAttaquantSurCible' => count($groupesAttaquants),
                    'pvActuelsCible' => $defenseur->getPvActuels($cible),
                    'pvMaximumCible' => $defenseur->getStickman($cible)->getPv() ?? 0,
                    'attaquants' => $acteursAttaquants,
                    'defenseurs' => $acteursDefenseurs,
                ],
            ];
        }
    }

    /**
     * Construit le contexte vivant d’un groupe pour l’évaluation des passifs.
     *
     * @return list<array{stickman: \App\Entity\Stickman, pvActuels: int, pvMaximum: int, partenaireVivant: bool, protegeAllie: bool}>
     */
    private function construireActeurs(
        EtatEquipeCombat $etat,
        string $groupe,
        ?string $cibleProtegee = null,
    ): array {
        $slots = $groupe === 'X' ? ['A', 'B'] : ['C', 'D'];
        $acteurs = [];

        foreach ($slots as $slot) {
            if (!$etat->estVivant($slot)) {
                continue;
            }

            $partenaire = $slot === 'A' ? 'B' : ($slot === 'B' ? 'A' : ($slot === 'C' ? 'D' : 'C'));
            $stickman = $etat->getStickman($slot);
            $acteurs[] = [
                'stickman' => $stickman,
                'pvActuels' => $etat->getPvActuels($slot),
                'pvMaximum' => $stickman->getPv() ?? 0,
                'partenaireVivant' => $etat->estVivant($partenaire),
                'protegeAllie' => $cibleProtegee !== null && $cibleProtegee !== $slot,
            ];
        }

        return $acteurs;
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
