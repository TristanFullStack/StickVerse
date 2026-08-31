<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\PlanRoundCombat;
use App\Entity\ResultatRoundCombat;
use App\Model\EtatEquipeCombat;
use App\Repository\CombatRepository;
use App\Repository\CombattantCombatRepository;
use App\Repository\PlanRoundCombatRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final class ResolutionRoundCombatEnLigneService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatRepository $combatRepository,
        private readonly PlanRoundCombatRepository $planRepository,
        private readonly CombattantCombatRepository $combattantRepository,
        private readonly CreationEtatEquipeCombatDepuisSnapshotsService $creationEtatEquipeService,
        private readonly ResolutionRoundService $resolutionRoundService,
        private readonly DeterminationFinCombatService $determinationFinCombatService,
    ) {
    }

    /**
     * @return array<string, array<string, int>>|null
     */
    public function resoudreSiPret(int $combatId): ?array
    {
        return $this->entityManager->wrapInTransaction(
            function () use ($combatId): ?array {
                $combat = $this->combatRepository
                    ->trouverAvecVerrouEcriture($combatId);

                if (!$combat instanceof Combat) {
                    throw new LogicException(
                        'Le combat demandé est introuvable.'
                    );
                }

                if (!$combat->estEnCours()) {
                    throw new LogicException(
                        'Seul un combat en cours peut être résolu.'
                    );
                }

                $joueur1 = $combat->getJoueur1();
                $joueur2 = $combat->getJoueur2();

                if ($joueur2 === null) {
                    throw new LogicException(
                        'Le combat doit posséder deux joueurs.'
                    );
                }

                $numeroRound = $combat->getNumeroRound();

                $plans = $this->planRepository
                    ->trouverPourCombatEtRound(
                        $combat,
                        $numeroRound,
                    );

                /*
                 * Un seul plan signifie que l’adversaire
                 * n’a pas encore joué.
                 */
                if (count($plans) < 2) {
                    return null;
                }

                if (count($plans) > 2) {
                    throw new LogicException(
                        'Le round contient trop de plans.'
                    );
                }

                $planJoueur1 = null;
                $planJoueur2 = null;

                foreach ($plans as $plan) {
                    if (!$plan instanceof PlanRoundCombat) {
                        throw new LogicException(
                            'Un plan du round est invalide.'
                        );
                    }

                    if ($plan->getJoueur() === $joueur1) {
                        if ($planJoueur1 !== null) {
                            throw new LogicException(
                                'Le joueur 1 possède plusieurs plans.'
                            );
                        }

                        $planJoueur1 = $plan;

                        continue;
                    }

                    if ($plan->getJoueur() === $joueur2) {
                        if ($planJoueur2 !== null) {
                            throw new LogicException(
                                'Le joueur 2 possède plusieurs plans.'
                            );
                        }

                        $planJoueur2 = $plan;

                        continue;
                    }

                    throw new LogicException(
                        'Un plan appartient à un joueur extérieur au combat.'
                    );
                }

                if (
                    !$planJoueur1 instanceof PlanRoundCombat
                    || !$planJoueur2 instanceof PlanRoundCombat
                ) {
                    throw new LogicException(
                        'Chaque participant doit posséder un plan.'
                    );
                }

                $combattantsJoueur1 = $this->combattantRepository
                    ->trouverPourCombatEtJoueur(
                        $combat,
                        $joueur1,
                    );

                $combattantsJoueur2 = $this->combattantRepository
                    ->trouverPourCombatEtJoueur(
                        $combat,
                        $joueur2,
                    );

                $etatJoueur1 = $this->creationEtatEquipeService
                    ->creer($combattantsJoueur1);

                $etatJoueur2 = $this->creationEtatEquipeService
                    ->creer($combattantsJoueur2);

                $resultats = $this->resolutionRoundService->resoudre(
                    joueur1: $etatJoueur1,
                    planJoueur1: $planJoueur1->toPlanCombat(),
                    joueur2: $etatJoueur2,
                    planJoueur2: $planJoueur2->toPlanCombat(),
                    numeroRound: $numeroRound,
                );

                $this->appliquerPvAuxSnapshots(
                    $combattantsJoueur1,
                    $etatJoueur1,
                );

                $this->appliquerPvAuxSnapshots(
                    $combattantsJoueur2,
                    $etatJoueur2,
                );

                $combat->enregistrerResultatsRound(
                    $numeroRound,
                    $resultats,
                );

                new ResultatRoundCombat(
                    $combat,
                    $numeroRound,
                    $resultats,
                );

                /*
                 * Les nouveaux PV sont maintenant présents
                 * dans les snapshots persistants.
                 *
                 * Le service peut donc déterminer si le combat
                 * possède un gagnant ou se termine par un match nul.
                 */
                $combatTermine =
                    $this->determinationFinCombatService
                        ->terminerSiNecessaire(
                            combat: $combat,
                            combattantsJoueur1: $combattantsJoueur1,
                            combattantsJoueur2: $combattantsJoueur2,
                        );

                /*
                 * Un combat terminé conserve le numéro du dernier
                 * round réellement joué.
                 *
                 * Si le combat continue, le changement de numéro
                 * empêche la double résolution du round courant.
                 */
                if (!$combatTermine) {
                    $combat->passerAuRoundSuivant();
                }

                return $resultats;
            }
        );
    }

    /**
     * @param list<CombattantCombat> $combattants
     */
    private function appliquerPvAuxSnapshots(
        array $combattants,
        EtatEquipeCombat $etatEquipe,
    ): void {
        foreach ($combattants as $combattant) {
            $combattant->setPvActuels(
                $etatEquipe->getPvActuels(
                    $combattant->getSlot()
                )
            );
        }
    }
}
