<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\MouvementPieces;
use App\Entity\User;
use App\Service\MouvementPiecesService;
use LogicException;

final class RecompenseCombatService
{
    public const RECOMPENSE_VICTOIRE = 100;
    public const RECOMPENSE_DEFAITE = 25;
    public const RECOMPENSE_MATCH_NUL = 50;

    public function __construct(
        private readonly ?MouvementPiecesService $mouvementPiecesService = null,
    ) {
    }

    /**
     * @return array{joueur1: int, joueur2: int}
     */
    public function attribuerSiNecessaire(Combat $combat): array
    {
        if ($combat->estRecompenseAttribuee()) {
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
            throw new LogicException(
                'Le combat doit posséder deux joueurs pour attribuer une récompense.'
            );
        }

        if ($combat->estTermine()) {
            $gagnant = $combat->getGagnant();

            if ($gagnant === null) {
                $recompenses = [
                    'joueur1' => self::RECOMPENSE_MATCH_NUL,
                    'joueur2' => self::RECOMPENSE_MATCH_NUL,
                ];
            } else {
                $recompenses = $this->recompensesPourGagnant(
                    $gagnant,
                    $joueur1,
                    $joueur2,
                );
            }
        } else {
            $gagnant = $combat->getGagnant();

            if ($gagnant === null) {
                throw new LogicException(
                    'Un forfait ou un abandon doit posséder un gagnant.'
                );
            }

            $recompenses = $this->recompensesPourGagnant(
                $gagnant,
                $joueur1,
                $joueur2,
                recompenseDefaite: 0,
            );
        }

        if ($recompenses['joueur1'] > 0) {
            $joueur1->crediterPieces($recompenses['joueur1']);
            $this->enregistrerMouvement(
                $joueur1,
                $recompenses['joueur1'],
                $combat,
            );
        }

        if ($recompenses['joueur2'] > 0) {
            $joueur2->crediterPieces($recompenses['joueur2']);
            $this->enregistrerMouvement(
                $joueur2,
                $recompenses['joueur2'],
                $combat,
            );
        }

        $combat->marquerRecompenseAttribuee();

        return $recompenses;
    }

    public function montantPour(
        Combat $combat,
        User $joueur,
    ): int {
        if (
            !$combat->estTermine()
            && !$combat->estAbandonne()
            && !$combat->estForfait()
        ) {
            return 0;
        }

        $joueur1 = $combat->getJoueur1();
        $joueur2 = $combat->getJoueur2();

        if (!$joueur2 instanceof User) {
            return 0;
        }

        if ($combat->estTermine() && $combat->getGagnant() === null) {
            return self::RECOMPENSE_MATCH_NUL;
        }

        $gagnant = $combat->getGagnant();

        if (!$gagnant instanceof User) {
            return 0;
        }

        if ($this->memeJoueur($joueur, $gagnant)) {
            return self::RECOMPENSE_VICTOIRE;
        }

        return $this->memeJoueur($joueur, $joueur1)
            || $this->memeJoueur($joueur, $joueur2)
            ? ($combat->estTermine() ? self::RECOMPENSE_DEFAITE : 0)
            : 0;
    }

    /**
     * @return array{joueur1: int, joueur2: int}
     */
    private function recompensesPourGagnant(
        User $gagnant,
        User $joueur1,
        User $joueur2,
        int $recompenseDefaite = self::RECOMPENSE_DEFAITE,
    ): array {
        if ($this->memeJoueur($gagnant, $joueur1)) {
            return [
                'joueur1' => self::RECOMPENSE_VICTOIRE,
                'joueur2' => $recompenseDefaite,
            ];
        }

        if ($this->memeJoueur($gagnant, $joueur2)) {
            return [
                'joueur1' => $recompenseDefaite,
                'joueur2' => self::RECOMPENSE_VICTOIRE,
            ];
        }

        throw new LogicException(
            'Le gagnant doit participer au combat.'
        );
    }

    private function memeJoueur(User $premier, User $second): bool
    {
        if ($premier === $second) {
            return true;
        }

        return $premier->getId() !== null
            && $premier->getId() === $second->getId();
    }

    private function enregistrerMouvement(
        User $joueur,
        int $montant,
        Combat $combat,
    ): void {
        $combatId = $combat->getId();
        $libelle = $combatId === null
            ? 'Récompense de combat'
            : 'Récompense du combat #'.$combatId;

        $this->mouvementPiecesService?->enregistrer(
            $joueur,
            $montant,
            MouvementPieces::TYPE_RECOMPENSE_COMBAT,
            $libelle,
        );
    }
}
