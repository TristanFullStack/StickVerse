<?php

namespace App\Service;

final class DivisionClassementService
{
    /**
     * @var list<array{nom: string, minimum: int, maximum: ?int, recompense: int}>
     */
    private const DIVISIONS = [
        [
            'nom' => 'Éclaireur',
            'minimum' => 0,
            'maximum' => 500,
            'recompense' => 1000,
        ],
        [
            'nom' => 'Sentinelle',
            'minimum' => 501,
            'maximum' => 1000,
            'recompense' => 5000,
        ],
        [
            'nom' => 'Stratège',
            'minimum' => 1001,
            'maximum' => 1500,
            'recompense' => 10000,
        ],
        [
            'nom' => 'Champion',
            'minimum' => 1501,
            'maximum' => 2000,
            'recompense' => 50000,
        ],
        [
            'nom' => 'Légende',
            'minimum' => 2001,
            'maximum' => 2500,
            'recompense' => 100000,
        ],
    ];

    /**
     * Retourne le barème public des divisions et des récompenses de fin de
     * saison. Les copies évitent qu’un appelant ne modifie la configuration.
     *
     * @return list<array{nom: string, minimum: int, maximum: ?int, recompense: int}>
     */
    public function definitions(): array
    {
        return array_map(
            static fn (array $division): array => $division,
            self::DIVISIONS,
        );
    }

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
            || $elo >= $division['maximum']
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
