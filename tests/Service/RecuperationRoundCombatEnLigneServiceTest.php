<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\PlanRoundCombat;
use App\Entity\User;
use App\Repository\PlanRoundCombatRepository;
use App\Service\RecuperationRoundCombatEnLigneService;
use PHPUnit\Framework\TestCase;

final class RecuperationRoundCombatEnLigneServiceTest extends TestCase
{
    public function testRecupereUnRoundPossedantDeuxPlans(): void
    {
        $joueur1 = (new User())->setEmail('joueur1@example.com');
        $joueur2 = (new User())->setEmail('joueur2@example.com');
        $combat = (new Combat($joueur1))
            ->setJoueur2($joueur2)
            ->setStatut(Combat::STATUT_EN_COURS);

        $this->attribuerId($combat, 42);

        $planRepository = $this->createMock(
            PlanRoundCombatRepository::class
        );
        $planRepository
            ->expects(self::once())
            ->method('trouverPourCombatEtRound')
            ->with($combat, 1)
            ->willReturn([
                $this->createStub(PlanRoundCombat::class),
                $this->createStub(PlanRoundCombat::class),
            ]);

        $service = new RecuperationRoundCombatEnLigneService(
            $planRepository,
        );

        self::assertTrue(
            $service->doitRecuperer($combat)
        );
    }

    public function testNeResoutPasUnRoundQuiAttendEncoreUnPlan(): void
    {
        $joueur1 = (new User())->setEmail('joueur1@example.com');
        $joueur2 = (new User())->setEmail('joueur2@example.com');
        $combat = (new Combat($joueur1))
            ->setJoueur2($joueur2)
            ->setStatut(Combat::STATUT_EN_COURS);

        $this->attribuerId($combat, 43);

        $planRepository = $this->createMock(
            PlanRoundCombatRepository::class
        );
        $planRepository
            ->expects(self::once())
            ->method('trouverPourCombatEtRound')
            ->with($combat, 1)
            ->willReturn([
                $this->createStub(PlanRoundCombat::class),
            ]);

        $service = new RecuperationRoundCombatEnLigneService(
            $planRepository,
        );

        self::assertFalse(
            $service->doitRecuperer($combat)
        );
    }

    public function testIgnoreUnCombatQuiNestPasJouable(): void
    {
        $combat = new Combat(
            (new User())->setEmail('joueur1@example.com')
        );

        $planRepository = $this->createMock(
            PlanRoundCombatRepository::class
        );
        $planRepository
            ->expects(self::never())
            ->method('trouverPourCombatEtRound');

        $service = new RecuperationRoundCombatEnLigneService(
            $planRepository,
        );

        self::assertFalse(
            $service->doitRecuperer($combat)
        );
    }

    private function attribuerId(Combat $combat, int $id): void
    {
        $propriete = new \ReflectionProperty(Combat::class, 'id');
        $propriete->setValue($combat, $id);
    }
}
