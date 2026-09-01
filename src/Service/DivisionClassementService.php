<?php

namespace App\Service;

final class DivisionClassementService
{
    /**
     * @var list<array{nom: string, minimum: int, maximum: ?int, recompense: int}>
     */
    private const DIVISIONS = [
        [
            'nom' => 'Bronze',
            'minimum' => 0,
            'maximum' => 999,
            'recompense' => 100,
        ],
        [
            'nom' => 'Argent',
            'minimum' => 1000,
            'maximum' => 1199,
            'recompense' => 200,
        ],
        [
            'nom' => 'Or',
            'minimum' => 1200,
            'maximum' => 1399,
            'recompense' => 350,
        ],
        [
            'nom' => 'Platine',
            'minimum' => 1400,
            'maximum' => 1599,
            'recompense' => 550,
        ],
        [
            'nom' => 'Diamant',
            'minimum' => 1600,
            'maximum' => null,
            'recompense' => 800,
        ],
    ];

    /**
     * @return array{nom: string, minimum: int, maximum: ?int, recompense: int, progression: int, prochainPalier: ?int, pointsRestants: int}
     */
    public function informationsPour(int $elo): array
    {
        $elo = max(0, $elo);
        $division = self::DIVISIONS[0];

        foreach (self::DIVISIONS as $candidate) {
            if ($elo >= $candidate['minimum']) {
                $division = $candidate;
            }
        }

        $prochainPalier = $division['maximum'] === null
            ? null
            : $division['maximum'] + 1;
        $progression = $prochainPalier === null
            ? 100
            : (int) floor(
                100 * ($elo - $division['minimum'])
                / ($prochainPalier - $division['minimum']),
            );

        return $division + [
            'progression' => max(0, min(100, $progression)),
            'prochainPalier' => $prochainPalier,
            'pointsRestants' => $prochainPalier === null
                ? 0
                : max(0, $prochainPalier - $elo),
        ];
    }
}
