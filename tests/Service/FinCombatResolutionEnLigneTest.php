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
use App\Service\DeterminationFinCombatService;
use App\Service\ResolutionRoundCombatEnLigneService;
use App\Service\ResolutionRoundService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class FinCombatResolutionEnLigneTest extends TestCase
{
    public function testTermineLeCombatSansPasserAuRoundSuivant(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        /*
         * Les deux groupes du joueur 1 attaquent le dernier
         * combattant vivant du joueur 2, situé dans le slot A.
         */
        $planJoueur1 = new PlanRoundCombat(
            $combat,
            $joueur1,
            new PlanCombat(
                cibleAttaqueX: 'A',
                cibleAttaqueY: 'A',
                cibleDefenseX: 'A',
                cibleDefenseY: 'A',
            ),
        );

        $planJoueur2 = new PlanRoundCombat(
            $combat,
            $joueur2,
            new PlanCombat(
                cibleAttaqueX: 'A',
                cibleAttaqueY: 'A',
                cibleDefenseX: 'A',
                cibleDefenseY: 'A',
            ),
        );

        /*
         * Le joueur 1 possède encore quatre combattants.
         * Chaque combattant possède 2 points d’attaque.
         */
        $etatJoueur1 = [
            'A' => [
                'pv' => 10,
                'attaque' => 2,
                'defense' => 0,
            ],
            'B' => [
                'pv' => 10,
                'attaque' => 2,
                'defense' => 0,
            ],
            'C' => [
                'pv' => 10,
                'attaque' => 2,
                'defense' => 0,
            ],
            'D' => [
                'pv' => 10,
                'attaque' => 2,
                'defense' => 0,
            ],
        ];

        /*
         * Le joueur 2 ne possède plus qu’un seul combattant
         * vivant. Son slot A possède seulement 1 PV.
         */
        $etatJoueur2 = [
            'A' => [
                'pv' => 1,
                'attaque' => 0,
                'defense' => 0,
            ],
            'B' => [
                'pv' => 0,
                'attaque' => 0,
                'defense' => 0,
            ],
            'C' => [
                'pv' => 0,
                'attaque' => 0,
                'defense' => 0,
            ],
            'D' => [
                'pv' => 0,
                'attaque' => 0,
                'defense' => 0,
            ],
        ];

        $combattantsJoueur1 = $this->creerSnapshots(
            $combat,
            $joueur1,
            $etatJoueur1,
        );

        $combattantsJoueur2 = $this->creerSnapshots(
            $combat,
            $joueur2,
            $etatJoueur2,
        );

        $entityManager = $this->createMock(
            EntityManagerInterface::class
        );

        $entityManager
            ->expects(self::once())
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
            ->willReturn([
                $planJoueur1,
                $planJoueur2,
            ]);

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
                        default => [],
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
            new DeterminationFinCombatService(),
        );

        $resultats = $service->resoudreSiPret(42);

        self::assertNotNull($resultats);

        self::assertSame(
            0,
            $resultats['joueur2_A']['pvRestants'],
        );

        self::assertSame(0, $etatJoueur2['A']['pv']);

        /*
         * Le combat est terminé avec le joueur 1 comme gagnant.
         */
        self::assertTrue($combat->estTermine());
        self::assertSame($joueur1, $combat->getGagnant());

        /*
         * Le numéro reste à 1 car le combat s’est terminé
         * pendant le premier round.
         */
        self::assertSame(1, $combat->getNumeroRound());
    }

    /**
     * @param array<string, array{
     *     pv: int,
     *     attaque: int,
     *     defense: int
     * }> $etatParSlot
     *
     * @return list<CombattantCombat>
     */
    private function creerSnapshots(
        Combat $combat,
        User $joueur,
        array &$etatParSlot,
    ): array {
        $combattants = [];

        foreach ($etatParSlot as $slot => $caracteristiques) {
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
                        &$etatParSlot,
                        $slot,
                    ): int {
                        return $etatParSlot[$slot]['pv'];
                    }
                );

            $combattant
                ->method('getAttaqueSnapshot')
                ->willReturn(
                    $caracteristiques['attaque']
                );

            $combattant
                ->method('getDefenseSnapshot')
                ->willReturn(
                    $caracteristiques['defense']
                );

            $combattant
                ->method('estVivant')
                ->willReturnCallback(
                    static function () use (
                        &$etatParSlot,
                        $slot,
                    ): bool {
                        return $etatParSlot[$slot]['pv'] > 0;
                    }
                );

            $combattant
                ->expects(self::once())
                ->method('setPvActuels')
                ->willReturnCallback(
                    static function (int $nouveauxPv) use (
                        &$etatParSlot,
                        $slot,
                        $combattant,
                    ): CombattantCombat {
                        $etatParSlot[$slot]['pv'] = $nouveauxPv;

                        return $combattant;
                    }
                );

            $combattants[] = $combattant;
        }

        return $combattants;
    }
}