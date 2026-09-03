<?php

namespace App\Service;

use App\Entity\Stickman;
use InvalidArgumentException;

final class CombatService
{
    public function __construct(
        private readonly ?PassifCombatService $passifService = null,
    ) {
    }

    public function bonusPressionAttaque(int $numeroRound): int
    {
        if ($numeroRound < 1) {
            throw new InvalidArgumentException(
                'Le numéro du round doit être supérieur à zéro.'
            );
        }

        if ($numeroRound <= 9) {
            return intdiv($numeroRound - 1, 3) * 10;
        }

        return 30 + (($numeroRound - 10) * 10);
    }

    /**
     * @return array{
     *     attaque: int,
     *     defense: int,
     *     degatsCalcules: int,
     *     degatsEffectifs: int,
     *     overkill: int,
     *     pvAvant: int,
     *     pvRestants: int,
     *     bonusPassifsAttaque?: int,
     *     bonusPassifsDefense?: int,
     *     passifsActifs?: list<array<string, mixed>>
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
    public function calculerAttaqueTotale(
        array $stickmen,
        int $numeroRound = 1,
    ): int
    {
        $attaqueTotale = 0;

        foreach ($stickmen as $stickman) {
            $attaqueTotale += $stickman->getAttaque() ?? 0;
        }

        $bonusPression = $this->bonusPressionAttaque($numeroRound);
        $bonusPassifs = ($this->passifService ?? new PassifCombatService())
            ->bonusAttaquePourcentage($stickmen, $numeroRound);

        return (int) round(
            $attaqueTotale
            * (1 + (($bonusPression + $bonusPassifs) / 100))
        );
    }

    /**
     * @param list<Stickman> $stickmen
     */
    public function calculerDefenseTotale(
        array $stickmen,
        int $numeroRound = 1,
    ): int
    {
        $defenseTotale = 0;

        foreach ($stickmen as $stickman) {
            $defenseTotale += $stickman->getDefense() ?? 0;
        }

        $bonusPassifs = ($this->passifService ?? new PassifCombatService())
            ->bonusDefensePourcentage($stickmen, $numeroRound);

        return (int) round(
            $defenseTotale * (1 + ($bonusPassifs / 100))
        );
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
     *     pvRestants: int,
     *     bonusPassifsAttaque?: int,
     *     bonusPassifsDefense?: int,
     *     passifsActifs?: list<array<string, mixed>>
     * }
     */
    public function resoudreCible(
        array $attaquants,
        array $defenseurs,
        int $pvActuels,
        int $numeroRound = 1,
    ): array {
        $attaqueTotale = $this->calculerAttaqueTotale(
            $attaquants,
            $numeroRound,
        );
        $defenseTotale = $this->calculerDefenseTotale(
            $defenseurs,
            $numeroRound,
        );
        $passifService = $this->passifService ?? new PassifCombatService();
        $resultat = $this->calculerImpact(
            attaqueTotale: $attaqueTotale,
            defenseTotale: $defenseTotale,
            pvActuels: $pvActuels,
        );

        $resultat['bonusPassifsAttaque'] = $passifService
            ->bonusAttaquePourcentage($attaquants, $numeroRound);
        $resultat['bonusPassifsDefense'] = $passifService
            ->bonusDefensePourcentage($defenseurs, $numeroRound);
        $resultat['passifsActifs'] = array_merge(
            $passifService->passifsActifs($attaquants, $numeroRound),
            $passifService->passifsActifs($defenseurs, $numeroRound),
        );

        return $resultat;
    }

    /**
     * @param array<string, array{
     *     attaquants: list<Stickman>,
     *     defenseurs: list<Stickman>,
     *     pvActuels: int
     * }> $impacts
     *
     * @return array<string, array{
     *     attaque: int,
     *     defense: int,
     *     degatsCalcules: int,
     *     degatsEffectifs: int,
     *     overkill: int,
     *     pvAvant: int,
     *     pvRestants: int,
     *     bonusPassifsAttaque?: int,
     *     bonusPassifsDefense?: int,
     *     passifsActifs?: list<array<string, mixed>>
     * }>
     */
    public function resoudreRound(
        array $impacts,
        int $numeroRound = 1,
    ): array
    {
        $resultats = [];

        foreach ($impacts as $cible => $impact) {
            $resultats[$cible] = $this->resoudreCible(
                attaquants: $impact['attaquants'],
                defenseurs: $impact['defenseurs'],
                pvActuels: $impact['pvActuels'],
                numeroRound: $numeroRound,
            );
        }

        return $resultats;
    }

}
