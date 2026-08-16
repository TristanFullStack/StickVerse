<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Entity\User;
use LogicException;

final class CreationCombattantsCombatService
{
    /**
     * @return list<CombattantCombat>
     */
    public function creerPourJoueur(
        Combat $combat,
        User $joueur,
        Equipe $equipe,
    ): array {
        $this->verifierParticipation($combat, $joueur);
        $this->verifierProprietaireEquipe($equipe, $joueur);
        $this->verifierAbsenceDeSnapshots($combat, $joueur);

        $stickmenParSlot = [
            'A' => $equipe->getStickmanA(),
            'B' => $equipe->getStickmanB(),
            'C' => $equipe->getStickmanC(),
            'D' => $equipe->getStickmanD(),
        ];

        $identifiants = [];

        foreach ($stickmenParSlot as $slot => $stickman) {
            if (!$stickman instanceof Stickman) {
                throw new LogicException(
                    sprintf('Le slot %s ne contient aucun Stickman.', $slot)
                );
            }

            if ($stickman->getId() === null) {
                throw new LogicException(
                    sprintf(
                        'Le Stickman du slot %s doit être enregistré en base de données.',
                        $slot
                    )
                );
            }

            $identifiants[] = $stickman->getId();
        }

        if (count(array_unique($identifiants)) !== 4) {
            throw new LogicException(
                'Les quatre slots doivent contenir des Stickmans différents.'
            );
        }

        $combattants = [];

        foreach ($stickmenParSlot as $slot => $stickman) {
            $combattants[] = new CombattantCombat(
                combat: $combat,
                joueur: $joueur,
                slot: $slot,
                stickman: $stickman,
            );
        }

        return $combattants;
    }

    private function verifierParticipation(
        Combat $combat,
        User $joueur,
    ): void {
        $estJoueur1 = $this->utilisateursIdentiques(
            $joueur,
            $combat->getJoueur1(),
        );

        $estJoueur2 = $this->utilisateursIdentiques(
            $joueur,
            $combat->getJoueur2(),
        );

        if (!$estJoueur1 && !$estJoueur2) {
            throw new LogicException(
                'Cet utilisateur ne participe pas à ce combat.'
            );
        }
    }

    private function verifierProprietaireEquipe(
        Equipe $equipe,
        User $joueur,
    ): void {
        if (
            !$this->utilisateursIdentiques(
                $joueur,
                $equipe->getUtilisateur(),
            )
        ) {
            throw new LogicException(
                'Cette équipe n’appartient pas à ce joueur.'
            );
        }
    }

    private function verifierAbsenceDeSnapshots(
        Combat $combat,
        User $joueur,
    ): void {
        foreach ($combat->getCombattants() as $combattant) {
            if (
                $this->utilisateursIdentiques(
                    $joueur,
                    $combattant->getJoueur(),
                )
            ) {
                throw new LogicException(
                    'Les snapshots de ce joueur existent déjà pour ce combat.'
                );
            }
        }
    }

    private function utilisateursIdentiques(
        User $premier,
        ?User $second,
    ): bool {
        if ($second === null) {
            return false;
        }

        if ($premier === $second) {
            return true;
        }

        return $premier->getId() !== null
            && $premier->getId() === $second->getId();
    }
}