<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\PlanRoundCombat;
use App\Entity\User;
use App\Model\PlanCombat;
use App\Repository\CombatRepository;
use App\Repository\CombattantCombatRepository;
use App\Repository\PlanRoundCombatRepository;
use App\Service\CombatService;
use App\Service\CreationEtatEquipeCombatDepuisSnapshotsService;
use App\Service\ResolutionRoundCombatEnLigneService;
use App\Service\ResolutionRoundService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;

final class PersistancePvEntreRoundsTest extends TestCase
{
    public function testConserveLesPvEntreDeuxRoundsSuccessifs(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $plansParRound = [
            1 => $this->creerPlans(
                $combat,
                $joueur1,
                $joueur2,
            ),
        ];

        $pvJoueur1 = [
            'A' => 10,
            'B' => 10,
            'C' => 10,
            'D' => 10,
        ];

        $pvJoueur2 = [
            'A' => 10,
            'B' => 10,
            'C' => 10,
            'D' => 10,
        ];

        $combattantsJoueur1 =
            $this->creerSnapshotsPersistants(
                $combat,
                $joueur1,
                $pvJoueur1,
            );

        $combattantsJoueur2 =
            $this->creerSnapshotsPersistants(
                $combat,
                $joueur2,
                $pvJoueur2,
            );

        $entityManager = $this->createMock(
            EntityManagerInterface::class
        );

        $entityManager
            ->expects(self::exactly(2))
            ->method('wrapInTransaction')
            ->willReturnCallback(
                static function (callable $operation): mixed {
                    return $operation();
                }
            );

        $combatRepository = $this->createStub(
            CombatRepository::class
        );

        $combatRepository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn($combat);

        $planRepository = $this->createStub(
            PlanRoundCombatRepository::class
        );

        $planRepository
            ->method('trouverPourCombatEtRound')
            ->willReturnCallback(
                static function (
                    Combat $combatRecu,
                    int $numeroRound,
                ) use (
                    $combat,
                    &$plansParRound,
                ): array {
                    self::assertSame($combat, $combatRecu);

                    return $plansParRound[$numeroRound] ?? [];
                }
            );

        $combattantRepository = $this->createStub(
            CombattantCombatRepository::class
        );

        $combattantRepository
            ->method('trouverPourCombatEtJoueur')
            ->willReturnCallback(
                static function (
                    Combat $combatRecu,
                    User $joueur,
                ) use (
                    $combat,
                    $joueur1,
                    $joueur2,
                    $combattantsJoueur1,
                    $combattantsJoueur2,
                ): array {
                    self::assertSame($combat, $combatRecu);

                    return match ($joueur) {
                        $joueur1 => $combattantsJoueur1,
                        $joueur2 => $combattantsJoueur2,
                        default => throw new LogicException(
                            'Le joueur demandé est inconnu.'
                        ),
                    };
                }
            );

        $service = new ResolutionRoundCombatEnLigneService(
            $entityManager,
            $combatRepository,
            $planRepository,
            $combattantRepository,
            new CreationEtatEquipeCombatDepuisSnapshotsService(),
            new ResolutionRoundService(
                new CombatService()
            ),
        );

        /*
         * Round 1 :
         * les slots A et B de chaque joueur perdent 2 PV.
         */
        $resultatRound1 = $service->resoudreSiPret(42);

        self::assertNotNull($resultatRound1);
        self::assertSame(8, $pvJoueur1['A']);
        self::assertSame(8, $pvJoueur1['B']);
        self::assertSame(10, $pvJoueur1['C']);
        self::assertSame(10, $pvJoueur1['D']);

        self::assertSame(8, $pvJoueur2['A']);
        self::assertSame(8, $pvJoueur2['B']);
        self::assertSame(10, $pvJoueur2['C']);
        self::assertSame(10, $pvJoueur2['D']);

        self::assertSame(2, $combat->getNumeroRound());

        /*
         * Les nouveaux plans sont créés après le passage
         * du combat au round 2.
         */
        $plansParRound[2] = $this->creerPlans(
            $combat,
            $joueur1,
            $joueur2,
        );

        /*
         * Round 2 :
         * les dégâts repartent de 8 PV.
         * Les slots A et B doivent donc terminer à 6 PV.
         */
        $resultatRound2 = $service->resoudreSiPret(42);

        self::assertNotNull($resultatRound2);
        self::assertSame(
            8,
            $resultatRound2['joueur1_A']['pvAvant'],
        );
        self::assertSame(
            6,
            $resultatRound2['joueur1_A']['pvRestants'],
        );
        self::assertSame(
            8,
            $resultatRound2['joueur2_A']['pvAvant'],
        );
        self::assertSame(
            6,
            $resultatRound2['joueur2_A']['pvRestants'],
        );

        self::assertSame(6, $pvJoueur1['A']);
        self::assertSame(6, $pvJoueur1['B']);
        self::assertSame(10, $pvJoueur1['C']);
        self::assertSame(10, $pvJoueur1['D']);

        self::assertSame(6, $pvJoueur2['A']);
        self::assertSame(6, $pvJoueur2['B']);
        self::assertSame(10, $pvJoueur2['C']);
        self::assertSame(10, $pvJoueur2['D']);

        self::assertSame(3, $combat->getNumeroRound());
    }

    /**
     * @return list<PlanRoundCombat>
     */
    private function creerPlans(
        Combat $combat,
        User $joueur1,
        User $joueur2,
    ): array {
        return [
            new PlanRoundCombat(
                $combat,
                $joueur1,
                new PlanCombat('A', 'B', 'C', 'D'),
            ),
            new PlanRoundCombat(
                $combat,
                $joueur2,
                new PlanCombat('A', 'B', 'C', 'D'),
            ),
        ];
    }

    /**
     * @param array<string, int> $pvActuels
     *
     * @return list<CombattantCombat>
     */
    private function creerSnapshotsPersistants(
        Combat $combat,
        User $joueur,
        array &$pvActuels,
    ): array {
        $combattants = [];

        foreach (['A', 'B', 'C', 'D'] as $slot) {
            $combattant = $this->createMock(
                CombattantCombat::class
            );

            $combattant
                ->method('getCombat')
                ->willReturn($combat);

            $combattant
                ->method('getJoueur')
                ->willReturn($joueur);

            $combattant
                ->method('getSlot')
                ->willReturn($slot);

            $combattant
                ->method('getPvMaximum')
                ->willReturn(10);

            $combattant
                ->method('getPvActuels')
                ->willReturnCallback(
                    static function () use (
                        &$pvActuels,
                        $slot,
                    ): int {
                        return $pvActuels[$slot];
                    }
                );

            $combattant
                ->method('getAttaqueSnapshot')
                ->willReturn(1);

            $combattant
                ->method('getDefenseSnapshot')
                ->willReturn(0);

            $combattant
                ->expects(self::exactly(2))
                ->method('setPvActuels')
                ->willReturnCallback(
                    static function (int $nouveauxPv) use (
                        &$pvActuels,
                        $slot,
                        $combattant,
                    ): CombattantCombat {
                        $pvActuels[$slot] = $nouveauxPv;

                        return $combattant;
                    }
                );

            $combattants[] = $combattant;
        }

        return $combattants;
    }
}