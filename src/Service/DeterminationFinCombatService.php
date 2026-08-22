<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\User;
use LogicException;

final class DeterminationFinCombatService
{
    /**
     * @param list<CombattantCombat> $combattantsJoueur1
     * @param list<CombattantCombat> $combattantsJoueur2
     */
    public function terminerSiNecessaire(
        Combat $combat,
        array $combattantsJoueur1,
        array $combattantsJoueur2,
    ): bool {
        $joueur2 = $combat->getJoueur2();

        if (!$joueur2 instanceof User) {
            throw new LogicException(
                'Le combat doit posséder deux joueurs.'
            );
        }

        $vivantsJoueur1 = $this->filtrerVivants(
            $combattantsJoueur1
        );

        $vivantsJoueur2 = $this->filtrerVivants(
            $combattantsJoueur2
        );

        /*
         * Les deux équipes ont été éliminées pendant
         * le même round : élimination simultanée.
         */
        if (
            $vivantsJoueur1 === []
            && $vivantsJoueur2 === []
        ) {
            return $this->terminer(
                combat: $combat,
                gagnant: null,
            );
        }

        if ($vivantsJoueur1 === []) {
            return $this->terminer(
                combat: $combat,
                gagnant: $joueur2,
            );
        }

        if ($vivantsJoueur2 === []) {
            return $this->terminer(
                combat: $combat,
                gagnant: $combat->getJoueur1(),
            );
        }

        /*
         * Le match nul mathématique n'est possible
         * que lorsqu'il reste exactement un combattant
         * vivant de chaque côté.
         */
        if (
            count($vivantsJoueur1) !== 1
            || count($vivantsJoueur2) !== 1
        ) {
            return false;
        }

        $dernierJoueur1 = $vivantsJoueur1[0];
        $dernierJoueur2 = $vivantsJoueur2[0];

        $degatsPossiblesJoueur1 = max(
            0,
            $dernierJoueur1->getAttaqueSnapshot()
                - $dernierJoueur2->getDefenseSnapshot(),
        );

        $degatsPossiblesJoueur2 = max(
            0,
            $dernierJoueur2->getAttaqueSnapshot()
                - $dernierJoueur1->getDefenseSnapshot(),
        );

        /*
         * Si au moins l'un des deux combattants peut
         * encore infliger des dégâts, le combat continue.
         */
        if (
            $degatsPossiblesJoueur1 > 0
            || $degatsPossiblesJoueur2 > 0
        ) {
            return false;
        }

        return $this->terminer(
            combat: $combat,
            gagnant: null,
        );
    }

    private function terminer(
        Combat $combat,
        ?User $gagnant,
    ): bool {
        $combat->setGagnant($gagnant);
        $combat->setStatut(Combat::STATUT_TERMINE);

        return true;
    }

    /**
     * @param list<CombattantCombat> $combattants
     *
     * @return list<CombattantCombat>
     */
    private function filtrerVivants(array $combattants): array
    {
        $vivants = [];

        foreach ($combattants as $combattant) {
            if (!$combattant instanceof CombattantCombat) {
                throw new LogicException(
                    'La liste contient un combattant invalide.'
                );
            }

            if ($combattant->estVivant()) {
                $vivants[] = $combattant;
            }
        }

        return $vivants;
    }
}