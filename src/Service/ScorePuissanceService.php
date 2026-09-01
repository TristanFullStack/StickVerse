<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Entity\User;
use InvalidArgumentException;
use LogicException;

final class ScorePuissanceService
{
    public const COEFFICIENT_PV = 0.20;
    public const COEFFICIENT_ATTAQUE = 2.0;
    public const COEFFICIENT_DEFENSE = 1.5;

    public function calculerStickman(Stickman $stickman): int
    {
        $pv = $stickman->getPv();
        $attaque = $stickman->getAttaque();
        $defense = $stickman->getDefense();

        if ($pv === null || $attaque === null || $defense === null) {
            throw new LogicException(
                'Le Stickman doit posséder toutes ses statistiques pour calculer sa puissance.'
            );
        }

        return $this->calculerStatistiques($pv, $attaque, $defense);
    }

    public function calculerStatistiques(
        int $pv,
        int $attaque,
        int $defense,
    ): int {
        if ($pv <= 0 || $attaque < 0 || $defense < 0) {
            throw new InvalidArgumentException(
                'Les statistiques utilisées pour la puissance sont invalides.'
            );
        }

        return max(1, (int) round(
            ($pv * self::COEFFICIENT_PV)
            + ($attaque * self::COEFFICIENT_ATTAQUE)
            + ($defense * self::COEFFICIENT_DEFENSE),
        ));
    }

    public function calculerEquipe(Equipe $equipe): int
    {
        $stickmen = [
            $equipe->getStickmanA(),
            $equipe->getStickmanB(),
            $equipe->getStickmanC(),
            $equipe->getStickmanD(),
        ];
        $score = 0;

        foreach ($stickmen as $stickman) {
            if (!$stickman instanceof Stickman) {
                throw new LogicException(
                    'Les quatre Stickmans sont nécessaires pour calculer la puissance de l’équipe.'
                );
            }

            $score += $this->calculerStickman($stickman);
        }

        return $score;
    }

    public function calculerCombatPourJoueur(
        Combat $combat,
        User $joueur,
    ): int {
        $score = 0;

        foreach ($combat->getCombattants() as $combattant) {
            $proprietaire = $combattant->getJoueur();

            if (
                $proprietaire !== $joueur
                && (
                    $proprietaire->getId() === null
                    || $proprietaire->getId() !== $joueur->getId()
                )
            ) {
                continue;
            }

            $score += $this->calculerStatistiques(
                $combattant->getPvMaximum(),
                $combattant->getAttaqueSnapshot(),
                $combattant->getDefenseSnapshot(),
            );
        }

        return $score;
    }
}
