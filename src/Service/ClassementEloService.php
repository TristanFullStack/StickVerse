<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\User;

final class ClassementEloService
{
    public const K_FACTOR = 32;
    public const ECART_PUISSANCE_ELO_MAXIMUM = 300;

    public function __construct(
        private readonly ?ScorePuissanceService $scorePuissanceService = null,
    ) {
    }

    /**
     * @return array{joueur1: int, joueur2: int}
     */
    public function mettreAJourSiNecessaire(Combat $combat): array
    {
        if ($combat->estEloAttribuee()) {
            return [
                'joueur1' => 0,
                'joueur2' => 0,
            ];
        }

        if (
            !$combat->estTermine()
            && !$combat->estAbandonne()
            && !$combat->estForfait()
        ) {
            return [
                'joueur1' => 0,
                'joueur2' => 0,
            ];
        }

        $joueur1 = $combat->getJoueur1();
        $joueur2 = $combat->getJoueur2();

        if (!$joueur2 instanceof User) {
            return [
                'joueur1' => 0,
                'joueur2' => 0,
            ];
        }

        if ($combat->estPrive()) {
            $combat->marquerEloAttribuee();

            return [
                'joueur1' => 0,
                'joueur2' => 0,
            ];
        }

        $scoreJoueur1 = $this->scorePour($combat, $joueur1);
        $servicePuissance = $this->scorePuissanceService
            ?? new ScorePuissanceService();
        $puissanceJoueur1 = $servicePuissance
            ->calculerCombatPourJoueur($combat, $joueur1);
        $puissanceJoueur2 = $servicePuissance
            ->calculerCombatPourJoueur($combat, $joueur2);
        $attenduJoueur1 = $this->scoreAttendu(
            $joueur1->getElo(),
            $joueur2->getElo(),
            $puissanceJoueur1,
            $puissanceJoueur2,
        );
        $variationJoueur1 = (int) round(
            self::K_FACTOR * ($scoreJoueur1 - $attenduJoueur1),
        );
        $variationJoueur2 = -$variationJoueur1;

        $joueur1->modifierElo($variationJoueur1);
        $joueur2->modifierElo($variationJoueur2);
        $combat->marquerEloAttribuee();

        return [
            'joueur1' => $variationJoueur1,
            'joueur2' => $variationJoueur2,
        ];
    }

    private function scoreAttendu(
        int $eloJoueur,
        int $eloAdversaire,
        int $puissanceJoueur,
        int $puissanceAdversaire,
    ): float {
        $ecartPuissanceConvertiEnElo = 0.0;
        $puissanceMaximum = max($puissanceJoueur, $puissanceAdversaire);

        if ($puissanceMaximum > 0) {
            $ecartRelatif = (
                $puissanceAdversaire - $puissanceJoueur
            ) / $puissanceMaximum;
            $ecartPuissanceConvertiEnElo = max(
                -self::ECART_PUISSANCE_ELO_MAXIMUM,
                min(
                    self::ECART_PUISSANCE_ELO_MAXIMUM,
                    400 * $ecartRelatif,
                ),
            );
        }

        $ecartEffectif = ($eloAdversaire - $eloJoueur)
            + $ecartPuissanceConvertiEnElo;

        return 1 / (1 + 10 ** ($ecartEffectif / 400));
    }

    private function scorePour(Combat $combat, User $joueur): float
    {
        $gagnant = $combat->getGagnant();

        if ($gagnant === null) {
            return 0.5;
        }

        return $gagnant === $joueur || (
            $gagnant->getId() !== null
            && $gagnant->getId() === $joueur->getId()
        ) ? 1.0 : 0.0;
    }
}
