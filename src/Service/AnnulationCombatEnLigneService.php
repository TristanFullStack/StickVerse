<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\User;
use App\Repository\CombatRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final class AnnulationCombatEnLigneService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatRepository $combatRepository,
    ) {
    }

    public function annuler(
        int $combatId,
        User $joueur,
    ): Combat {
        return $this->entityManager->wrapInTransaction(
            function () use ($combatId, $joueur): Combat {
                $combat = $this->combatRepository
                    ->trouverAvecVerrouEcriture($combatId);

                if (!$combat instanceof Combat) {
                    throw new LogicException(
                        'Le combat demandé est introuvable.'
                    );
                }

                if (!$combat->estEnAttente()) {
                    throw new LogicException(
                        'Seul un combat en attente peut être annulé.'
                    );
                }

                if ($combat->getJoueur1() !== $joueur) {
                    throw new LogicException(
                        'Seul le créateur peut annuler ce combat.'
                    );
                }

                if ($combat->getJoueur2() instanceof User) {
                    throw new LogicException(
                        'Un combat rejoint ne peut plus être annulé.'
                    );
                }

                $combat->setGagnant(null);
                $combat->setStatut(Combat::STATUT_ANNULE);

                return $combat;
            }
        );
    }
}
