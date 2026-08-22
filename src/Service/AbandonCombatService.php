<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\User;
use App\Repository\CombatRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final class AbandonCombatService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatRepository $combatRepository,
    ) {
    }

    public function abandonner(
        int $combatId,
        User $joueur,
    ): Combat {
        return $this->entityManager->wrapInTransaction(
            function () use (
                $combatId,
                $joueur,
            ): Combat {
                $combat = $this->combatRepository
                    ->trouverAvecVerrouEcriture($combatId);

                if (!$combat instanceof Combat) {
                    throw new LogicException(
                        'Le combat demandé est introuvable.'
                    );
                }

                if (!$combat->estEnCours()) {
                    throw new LogicException(
                        'Seul un combat en cours peut être abandonné.'
                    );
                }

                if (!$combat->estParticipant($joueur)) {
                    throw new LogicException(
                        'Seul un participant peut abandonner ce combat.'
                    );
                }

                $joueur1 = $combat->getJoueur1();
                $joueur2 = $combat->getJoueur2();

                if (!$joueur2 instanceof User) {
                    throw new LogicException(
                        'Le combat doit posséder deux joueurs.'
                    );
                }

                $gagnant = $joueur === $joueur1
                    ? $joueur2
                    : $joueur1;

                $combat->setGagnant($gagnant);
                $combat->setStatut(
                    Combat::STATUT_ABANDONNE
                );

                return $combat;
            }
        );
    }
}