<?php

namespace App\Service;

use App\Entity\Equipe;
use App\Entity\Stickman;
use LogicException;

/**
 * Centralise les informations affichées par le constructeur d’équipe.
 *
 * Le constructeur JavaScript ne reçoit que les statistiques sérialisées par
 * le template, tandis que les mêmes calculs restent disponibles côté PHP
 * pour les équipes enregistrées et les règles métier.
 */
final class EquipeCompositionService
{
    public function __construct(
        private readonly ScorePuissanceService $scorePuissanceService,
    ) {
    }

    /**
     * @return array{
     *     total: array{puissance: int, pv: int, attaque: int, defense: int},
     *     groupes: array{
     *         X: array{puissance: int, pv: int, attaque: int, defense: int},
     *         Y: array{puissance: int, pv: int, attaque: int, defense: int}
     *     },
     *     slots: list<array{
     *         slot: string,
     *         groupe: string,
     *         stickman: Stickman,
     *         puissance: int
     *     }>
     * }
     */
    public function resumer(Equipe $equipe): array
    {
        $definitions = [
            ['slot' => 'A', 'groupe' => 'X', 'stickman' => $equipe->getStickmanA()],
            ['slot' => 'B', 'groupe' => 'X', 'stickman' => $equipe->getStickmanB()],
            ['slot' => 'C', 'groupe' => 'Y', 'stickman' => $equipe->getStickmanC()],
            ['slot' => 'D', 'groupe' => 'Y', 'stickman' => $equipe->getStickmanD()],
        ];

        $groupes = [
            'X' => $this->statistiquesVides(),
            'Y' => $this->statistiquesVides(),
        ];
        $total = $this->statistiquesVides();
        $slots = [];

        foreach ($definitions as $definition) {
            $stickman = $definition['stickman'];

            if (!$stickman instanceof Stickman) {
                throw new LogicException(
                    'Les quatre Stickmans sont nécessaires pour résumer une équipe.'
                );
            }

            $puissance = $this->scorePuissanceService->calculerStickman($stickman);
            $statistiques = [
                'puissance' => $puissance,
                'pv' => $stickman->getPv() ?? 0,
                'attaque' => $stickman->getAttaque() ?? 0,
                'defense' => $stickman->getDefense() ?? 0,
            ];
            $groupe = $definition['groupe'];

            foreach ($statistiques as $cle => $valeur) {
                $groupes[$groupe][$cle] += $valeur;
                $total[$cle] += $valeur;
            }

            $slots[] = [
                'slot' => $definition['slot'],
                'groupe' => $groupe,
                'stickman' => $stickman,
                'puissance' => $puissance,
            ];
        }

        return [
            'total' => $total,
            'groupes' => $groupes,
            'slots' => $slots,
        ];
    }

    /**
     * @return array{puissance: int, pv: int, attaque: int, defense: int}
     */
    private function statistiquesVides(): array
    {
        return [
            'puissance' => 0,
            'pv' => 0,
            'attaque' => 0,
            'defense' => 0,
        ];
    }
}
