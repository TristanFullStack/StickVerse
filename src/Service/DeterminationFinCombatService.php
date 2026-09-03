<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\User;
use LogicException;

final class DeterminationFinCombatService
{
    public const NOMBRE_ROUNDS_SANS_DEGAT_AVANT_MATCH_NUL = 3;
    public const NOMBRE_ROUNDS_MAX = Combat::NOMBRE_MAX_ROUNDS;

    public function __construct(
        private readonly ?RecompenseCombatService $recompenseService = null,
    ) {
    }

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
         * Trois rounds consécutifs sans aucun dégât effectif
         * indiquent que les choix des deux joueurs ont créé
         * une situation de blocage durable.
         */
        if ($this->nombreRoundsConsecutifsSansDegat($combat)
            >= self::NOMBRE_ROUNDS_SANS_DEGAT_AVANT_MATCH_NUL
        ) {
            return $this->terminer(
                combat: $combat,
                gagnant: null,
            );
        }

        if ($combat->getNumeroRound() >= self::NOMBRE_ROUNDS_MAX) {
            return $this->terminerSelonPvRestants(
                combat: $combat,
                combattantsJoueur1: $combattantsJoueur1,
                combattantsJoueur2: $combattantsJoueur2,
            );
        }

        return false;
    }

    /**
     * À la limite de rounds, le joueur qui conserve le plus de PV gagne.
     * Une égalité parfaite reste un match nul déterministe.
     *
     * @param list<CombattantCombat> $combattantsJoueur1
     * @param list<CombattantCombat> $combattantsJoueur2
     */
    private function terminerSelonPvRestants(
        Combat $combat,
        array $combattantsJoueur1,
        array $combattantsJoueur2,
    ): bool {
        $pvJoueur1 = array_sum(array_map(
            static fn (CombattantCombat $combattant): int =>
                $combattant->getPvActuels(),
            $combattantsJoueur1,
        ));
        $pvJoueur2 = array_sum(array_map(
            static fn (CombattantCombat $combattant): int =>
                $combattant->getPvActuels(),
            $combattantsJoueur2,
        ));

        $gagnant = match (true) {
            $pvJoueur1 > $pvJoueur2 => $combat->getJoueur1(),
            $pvJoueur2 > $pvJoueur1 => $combat->getJoueur2(),
            default => null,
        };

        return $this->terminer($combat, $gagnant);
    }

    private function nombreRoundsConsecutifsSansDegat(
        Combat $combat,
    ): int {
        $resultatsRounds = $combat
            ->getResultatsRounds()
            ->toArray();

        usort(
            $resultatsRounds,
            static fn ($a, $b): int =>
                $b->getNumeroRound() <=> $a->getNumeroRound(),
        );

        $nombreRounds = 0;

        foreach ($resultatsRounds as $resultatRound) {
            foreach ($resultatRound->getResultats() as $resultat) {
                if (
                    !isset($resultat['degatsEffectifs'])
                    || !is_int($resultat['degatsEffectifs'])
                    || $resultat['degatsEffectifs'] > 0
                ) {
                    return $nombreRounds;
                }
            }

            $nombreRounds++;
        }

        return $nombreRounds;
    }

    private function terminer(
        Combat $combat,
        ?User $gagnant,
    ): bool {
        $combat->setGagnant($gagnant);
        $combat->setStatut(Combat::STATUT_TERMINE);

        $this->recompenseService?->attribuerSiNecessaire($combat);

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
