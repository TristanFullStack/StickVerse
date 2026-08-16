<?php

namespace App\Service;

use App\Entity\Stickman;
use InvalidArgumentException;

final class CombatService
{
    /**
     * @return array{
     *     attaque: int,
     *     defense: int,
     *     degatsCalcules: int,
     *     degatsEffectifs: int,
     *     overkill: int,
     *     pvAvant: int,
     *     pvRestants: int
     * }
     */
    public function calculerImpact(
        int $attaqueTotale,
        int $defenseTotale,
        int $pvActuels,
    ): array {
        if ($attaqueTotale < 0 || $defenseTotale < 0 || $pvActuels < 0) {
            throw new InvalidArgumentException(
                'Les valeurs du combat ne peuvent pas être négatives.'
            );
        }

        $degatsCalcules = max(0, $attaqueTotale - $defenseTotale);
        $degatsEffectifs = min($pvActuels, $degatsCalcules);
        $overkill = max(0, $degatsCalcules - $pvActuels);
        $pvRestants = max(0, $pvActuels - $degatsEffectifs);

        return [
            'attaque' => $attaqueTotale,
            'defense' => $defenseTotale,
            'degatsCalcules' => $degatsCalcules,
            'degatsEffectifs' => $degatsEffectifs,
            'overkill' => $overkill,
            'pvAvant' => $pvActuels,
            'pvRestants' => $pvRestants,
        ];
    }

    /**
     * @param list<Stickman> $stickmen
     */
    public function calculerAttaqueTotale(array $stickmen): int
    {
        $attaqueTotale = 0;

        foreach ($stickmen as $stickman) {
            $attaqueTotale += $stickman->getAttaque() ?? 0;
        }

        return $attaqueTotale;
    }

    /**
     * @param list<Stickman> $stickmen
     */
    public function calculerDefenseTotale(array $stickmen): int
    {
        $defenseTotale = 0;

        foreach ($stickmen as $stickman) {
            $defenseTotale += $stickman->getDefense() ?? 0;
        }

        return $defenseTotale;
    }

    /**
     * @param list<Stickman> $attaquants
     * @param list<Stickman> $defenseurs
     *
     * @return array{
     *     attaque: int,
     *     defense: int,
     *     degatsCalcules: int,
     *     degatsEffectifs: int,
     *     overkill: int,
     *     pvAvant: int,
     *     pvRestants: int
     * }
     */
    public function resoudreCible(
        array $attaquants,
        array $defenseurs,
        int $pvActuels,
    ): array {
        $attaqueTotale = $this->calculerAttaqueTotale($attaquants);
        $defenseTotale = $this->calculerDefenseTotale($defenseurs);

        return $this->calculerImpact(
            attaqueTotale: $attaqueTotale,
            defenseTotale: $defenseTotale,
            pvActuels: $pvActuels,
        );
    }
}