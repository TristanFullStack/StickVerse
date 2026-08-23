<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\PlanRoundCombat;
use App\Entity\ResultatRoundCombat;
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
use LogicException;
use PHPUnit\Framework\TestCase;

final class ResolutionRoundCombatEnLigneServiceTest extends TestCase
{
    public function testResoutUneSeuleFoisEtConserveLesPv(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $planJoueur1 = new PlanRoundCombat(
            $combat,
            $joueur1,
            new PlanCombat('A', 'B', 'C', 'D'),
        );

        $planJoueur2 = new PlanRoundCombat(
            $combat,
            $joueur2,
            new PlanCombat('A', 'B', 'C', 'D'),
        );

        $combattantsJoueur1 = $this->creerCombattants(
            $combat,
            $joueur1,
            [
                'A' => 8,
                'B' => 8,
                'C' => 10,
                'D' => 10,
            ],
        );

        $combattantsJoueur2 = $this->creerCombattants(
            $combat,
            $joueur2,
            [
                'A' => 8,
                'B' => 8,
                'C' => 10,
                'D' => 10,
            ],
        );

        $entityManager = $this->creerEntityManagerTransactionnel(2);

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
                    $planJoueur1,
                    $planJoueur2,
                ): array {
                    self::assertSame($combat, $combatRecu);

                    if ($numeroRound === 1) {
                        return [
                            $planJoueur1,
                            $planJoueur2,
                        ];
                    }

                    return [];
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
                        default => [],
                    };
                }
            );

        $service = $this->creerService(
            $entityManager,
            $combatRepository,
            $planRepository,
            $combattantRepository,
        );

        $premiereResolution = $service->resoudreSiPret(42);

        self::assertNotNull($premiereResolution);
        self::assertSame(
            8,
            $premiereResolution['joueur1_A']['pvRestants'],
        );
        self::assertSame(
            8,
            $premiereResolution['joueur2_A']['pvRestants'],
        );
        self::assertSame(
            1,
            $combat->getDernierRoundResolu(),
        );
        self::assertSame(
            $premiereResolution,
            $combat->getDerniersResultats(),
        );
        self::assertCount(1, $combat->getResultatsRounds());

        $resultatHistorique = $combat
            ->getResultatsRounds()
            ->first();

        self::assertInstanceOf(
            ResultatRoundCombat::class,
            $resultatHistorique,
        );
        self::assertSame(
            $combat,
            $resultatHistorique->getCombat(),
        );
        self::assertSame(
            1,
            $resultatHistorique->getNumeroRound(),
        );
        self::assertSame(
            $premiereResolution,
            $resultatHistorique->getResultats(),
        );
        self::assertSame(2, $combat->getNumeroRound());

        /*
         * Le combat est maintenant au round 2.
         * Les plans du round 1 ne peuvent donc plus être rejoués.
         */
        $deuxiemeResolution = $service->resoudreSiPret(42);

        self::assertNull($deuxiemeResolution);
        self::assertSame(2, $combat->getNumeroRound());
        self::assertSame(
            1,
            $combat->getDernierRoundResolu(),
        );
        self::assertSame(
            $premiereResolution,
            $combat->getDerniersResultats(),
        );
        self::assertCount(1, $combat->getResultatsRounds());
    }

    public function testAttendLeDeuxiemePlan(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $planJoueur1 = new PlanRoundCombat(
            $combat,
            $joueur1,
            new PlanCombat('A', 'B', 'C', 'D'),
        );

        $entityManager = $this->creerEntityManagerTransactionnel(1);

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
            ->willReturn([$planJoueur1]);

        $combattantRepository = $this->createMock(
            CombattantCombatRepository::class
        );

        $combattantRepository
            ->expects(self::never())
            ->method('trouverPourCombatEtJoueur');

        $service = $this->creerService(
            $entityManager,
            $combatRepository,
            $planRepository,
            $combattantRepository,
        );

        $resultat = $service->resoudreSiPret(42);

        self::assertNull($resultat);
        self::assertSame(1, $combat->getNumeroRound());
        self::assertCount(0, $combat->getResultatsRounds());
    }

    public function testRefuseUnCombatQuiNestPasEnCours(): void
    {
        $combat = new Combat(new User());

        $entityManager = $this->creerEntityManagerTransactionnel(1);

        $combatRepository = $this->createStub(
            CombatRepository::class
        );

        $combatRepository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn($combat);

        $planRepository = $this->createMock(
            PlanRoundCombatRepository::class
        );

        $planRepository
            ->expects(self::never())
            ->method('trouverPourCombatEtRound');

        $combattantRepository = $this->createStub(
            CombattantCombatRepository::class
        );

        $service = $this->creerService(
            $entityManager,
            $combatRepository,
            $planRepository,
            $combattantRepository,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Seul un combat en cours peut être résolu.'
        );

        $service->resoudreSiPret(42);
    }

    /**
     * @param array<string, int> $pvAttendus
     *
     * @return list<CombattantCombat>
     */
    private function creerCombattants(
        Combat $combat,
        User $joueur,
        array $pvAttendus,
    ): array {
        $combattants = [];

        foreach ($pvAttendus as $slot => $pvAttendu) {
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
                ->willReturn(10);

            $combattant
                ->method('getAttaqueSnapshot')
                ->willReturn(1);

            $combattant
                ->method('getDefenseSnapshot')
                ->willReturn(0);

            /*
             * Les mocks PHPUnit retournent false par défaut
             * pour une méthode booléenne.
             *
             * Cette valeur doit donc être configurée pour que
             * le détecteur de fin voie correctement les survivants.
             */
            $combattant
                ->method('estVivant')
                ->willReturn($pvAttendu > 0);

            $combattant
                ->expects(self::once())
                ->method('setPvActuels')
                ->with($pvAttendu)
                ->willReturnSelf();

            $combattants[] = $combattant;
        }

        return $combattants;
    }

    private function creerEntityManagerTransactionnel(
        int $nombreAppels,
    ): EntityManagerInterface {
        $entityManager = $this->createMock(
            EntityManagerInterface::class
        );

        $entityManager
            ->expects(self::exactly($nombreAppels))
            ->method('wrapInTransaction')
            ->willReturnCallback(
                static function (callable $operation): mixed {
                    return $operation();
                }
            );

        return $entityManager;
    }

    private function creerService(
        EntityManagerInterface $entityManager,
        CombatRepository $combatRepository,
        PlanRoundCombatRepository $planRepository,
        CombattantCombatRepository $combattantRepository,
    ): ResolutionRoundCombatEnLigneService {
        return new ResolutionRoundCombatEnLigneService(
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
    }
}
