<?php

namespace App\Service;

use App\Entity\CombattantCombat;
use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Model\EtatEquipeCombat;
use InvalidArgumentException;
use LogicException;

final class CreationEtatEquipeCombatDepuisSnapshotsService
{
    private const SLOTS_VALIDES = ['A', 'B', 'C', 'D'];

    /**
     * @param iterable<CombattantCombat> $combattants
     */
    public function creer(iterable $combattants): EtatEquipeCombat
    {
        $combattantsParSlot = [];
        $combatReference = null;
        $joueurReference = null;

        foreach ($combattants as $combattant) {
            if (!$combattant instanceof CombattantCombat) {
                throw new InvalidArgumentException(
                    'Tous les éléments doivent être des combattants.'
                );
            }

            $combat = $combattant->getCombat();
            $joueur = $combattant->getJoueur();
            $slot = $combattant->getSlot();

            if ($combat === null) {
                throw new LogicException(
                    'Chaque combattant doit appartenir à un combat.'
                );
            }

            if (!$combat->estParticipant($joueur)) {
                throw new LogicException(
                    'Le joueur du combattant doit participer au combat.'
                );
            }

            if (!in_array($slot, self::SLOTS_VALIDES, true)) {
                throw new LogicException(
                    'Le slot doit être A, B, C ou D.'
                );
            }

            if ($combatReference === null) {
                $combatReference = $combat;
            } elseif ($combatReference !== $combat) {
                throw new LogicException(
                    'Les combattants doivent appartenir au même combat.'
                );
            }

            if ($joueurReference === null) {
                $joueurReference = $joueur;
            } elseif ($joueurReference !== $joueur) {
                throw new LogicException(
                    'Les combattants doivent appartenir au même joueur.'
                );
            }

            if (isset($combattantsParSlot[$slot])) {
                throw new LogicException(
                    sprintf('Le slot %s est présent plusieurs fois.', $slot)
                );
            }

            $combattantsParSlot[$slot] = $combattant;
        }

        foreach (self::SLOTS_VALIDES as $slot) {
            if (!isset($combattantsParSlot[$slot])) {
                throw new LogicException(
                    sprintf('Le combattant du slot %s est manquant.', $slot)
                );
            }
        }

        $equipe = new Equipe();

        $equipe->setStickmanA(
            $this->creerStickmanTemporaire($combattantsParSlot['A'])
        );

        $equipe->setStickmanB(
            $this->creerStickmanTemporaire($combattantsParSlot['B'])
        );

        $equipe->setStickmanC(
            $this->creerStickmanTemporaire($combattantsParSlot['C'])
        );

        $equipe->setStickmanD(
            $this->creerStickmanTemporaire($combattantsParSlot['D'])
        );

        $etatEquipe = new EtatEquipeCombat($equipe);

        foreach ($combattantsParSlot as $slot => $combattant) {
            $etatEquipe->appliquerPvRestants(
                slot: $slot,
                pvRestants: $combattant->getPvActuels(),
            );
        }

        return $etatEquipe;
    }

    private function creerStickmanTemporaire(
        CombattantCombat $combattant,
    ): Stickman {
        $stickman = new Stickman();

        $stickman->setPv($combattant->getPvMaximum());
        $stickman->setAttaque(
            $combattant->getAttaqueSnapshot()
        );
        $stickman->setDefense(
            $combattant->getDefenseSnapshot()
        );

        return $stickman;
    }
}