<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\PlanRoundCombat;
use App\Entity\User;
use App\Model\PlanCombat;
use App\Repository\CombatRepository;
use App\Repository\PlanRoundCombatRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final class SoumissionPlanCombatService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatRepository $combatRepository,
        private readonly PlanRoundCombatRepository $planRepository,
    ) {
    }

    public function soumettre(
        int $combatId,
        User $joueur,
        PlanCombat $plan,
    ): PlanRoundCombat {
        return $this->entityManager->wrapInTransaction(
            function () use (
                $combatId,
                $joueur,
                $plan,
            ): PlanRoundCombat {
                $combat = $this->combatRepository
                    ->trouverAvecVerrouEcriture($combatId);

                if (!$combat instanceof Combat) {
                    throw new LogicException(
                        'Le combat demandé est introuvable.'
                    );
                }

                if (!$combat->estEnCours()) {
                    throw new LogicException(
                        'Seul un combat en cours peut recevoir un plan.'
                    );
                }

                if (!$combat->getJoueur2() instanceof User) {
                    throw new LogicException(
                        'Le combat doit posséder deux joueurs.'
                    );
                }

                if (!$combat->estParticipant($joueur)) {
                    throw new LogicException(
                        'Seul un participant peut soumettre un plan.'
                    );
                }

                $numeroRound = $combat->getNumeroRound();

                $plansExistants = $this->planRepository
                    ->trouverPourCombatEtRound(
                        $combat,
                        $numeroRound,
                    );

                foreach ($plansExistants as $planExistant) {
                    if (!$planExistant instanceof PlanRoundCombat) {
                        throw new LogicException(
                            'Un plan enregistré est invalide.'
                        );
                    }

                    if ($planExistant->getJoueur() === $joueur) {
                        throw new LogicException(
                            'Le joueur a déjà soumis son plan pour ce round.'
                        );
                    }
                }

                $planRound = new PlanRoundCombat(
                    $combat,
                    $joueur,
                    $plan,
                );

                $this->entityManager->persist($planRound);

                return $planRound;
            }
        );
    }
}