<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\PlanRoundCombat;
use App\Entity\User;
use App\Model\PlanCombat;
use App\Repository\CombatRepository;
use App\Repository\PlanRoundCombatRepository;
use App\Service\SoumissionPlanCombatService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;

final class SoumissionPlanCombatServiceTest extends TestCase
{
    public function testEnregistreLePlanDuParticipant(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $plan = new PlanCombat(
            'A',
            'B',
            'C',
            'D',
        );

        $entityManager =
            $this->creerEntityManagerTransactionnel();

        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(
                self::callback(
                    static function (
                        object $entite,
                    ) use (
                        $combat,
                        $joueur1,
                    ): bool {
                        self::assertInstanceOf(
                            PlanRoundCombat::class,
                            $entite,
                        );

                        self::assertSame(
                            $combat,
                            $entite->getCombat(),
                        );

                        self::assertSame(
                            $joueur1,
                            $entite->getJoueur(),
                        );

                        self::assertSame(
                            1,
                            $entite->getNumeroRound(),
                        );

                        return true;
                    }
                )
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
            ->willReturn([]);

        $service = new SoumissionPlanCombatService(
            $entityManager,
            $combatRepository,
            $planRepository,
        );

        $planEnregistre = $service->soumettre(
            42,
            $joueur1,
            $plan,
        );

        self::assertSame(
            $combat,
            $planEnregistre->getCombat(),
        );

        self::assertSame(
            $joueur1,
            $planEnregistre->getJoueur(),
        );

        self::assertSame(
            1,
            $planEnregistre->getNumeroRound(),
        );

        self::assertSame(
            'A',
            $planEnregistre->getCibleAttaqueX(),
        );

        self::assertSame(
            'B',
            $planEnregistre->getCibleAttaqueY(),
        );

        self::assertSame(
            'C',
            $planEnregistre->getCibleDefenseX(),
        );

        self::assertSame(
            'D',
            $planEnregistre->getCibleDefenseY(),
        );
    }

    public function testRefuseUnDeuxiemePlanDuMemeJoueur(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $plan = new PlanCombat(
            'A',
            'B',
            'C',
            'D',
        );

        $planExistant = new PlanRoundCombat(
            $combat,
            $joueur1,
            $plan,
        );

        $entityManager =
            $this->creerEntityManagerTransactionnel();

        $entityManager
            ->expects(self::never())
            ->method('persist');

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
            ->willReturn([$planExistant]);

        $service = new SoumissionPlanCombatService(
            $entityManager,
            $combatRepository,
            $planRepository,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Le joueur a déjà soumis son plan pour ce round.'
        );

        $service->soumettre(
            42,
            $joueur1,
            $plan,
        );
    }

    public function testRefuseUnPlanAvantLesDeuxConfirmations(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();
        $combat = (new Combat($joueur1))
            ->setJoueur2($joueur2)
            ->setStatut(Combat::STATUT_EN_COURS)
            ->initialiserPreparation()
            ->confirmerPret($joueur1);

        $entityManager = $this->creerEntityManagerTransactionnel();
        $entityManager
            ->expects(self::never())
            ->method('persist');

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

        $service = new SoumissionPlanCombatService(
            $entityManager,
            $combatRepository,
            $planRepository,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Les deux joueurs doivent être prêts avant d’envoyer un plan.'
        );

        $service->soumettre(
            42,
            $joueur1,
            new PlanCombat('A', 'B', 'C', 'D'),
        );
    }

    public function testRefuseUnJoueurExterieur(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();
        $intrus = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $entityManager =
            $this->creerEntityManagerTransactionnel();

        $entityManager
            ->expects(self::never())
            ->method('persist');

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

        $service = new SoumissionPlanCombatService(
            $entityManager,
            $combatRepository,
            $planRepository,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Seul un participant peut soumettre un plan.'
        );

        $service->soumettre(
            42,
            $intrus,
            new PlanCombat('A', 'B', 'C', 'D'),
        );
    }

    public function testRefuseUnCombatQuiNestPasEnCours(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);

        $entityManager =
            $this->creerEntityManagerTransactionnel();

        $entityManager
            ->expects(self::never())
            ->method('persist');

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

        $service = new SoumissionPlanCombatService(
            $entityManager,
            $combatRepository,
            $planRepository,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Seul un combat en cours peut recevoir un plan.'
        );

        $service->soumettre(
            42,
            $joueur1,
            new PlanCombat('A', 'B', 'C', 'D'),
        );
    }

    public function testRefuseUnCombatIntrouvable(): void
    {
        $joueur = new User();

        $entityManager =
            $this->creerEntityManagerTransactionnel();

        $entityManager
            ->expects(self::never())
            ->method('persist');

        $combatRepository = $this->createStub(
            CombatRepository::class
        );

        $combatRepository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn(null);

        $planRepository = $this->createMock(
            PlanRoundCombatRepository::class
        );

        $planRepository
            ->expects(self::never())
            ->method('trouverPourCombatEtRound');

        $service = new SoumissionPlanCombatService(
            $entityManager,
            $combatRepository,
            $planRepository,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Le combat demandé est introuvable.'
        );

        $service->soumettre(
            42,
            $joueur,
            new PlanCombat('A', 'B', 'C', 'D'),
        );
    }

    private function creerEntityManagerTransactionnel(
    ): EntityManagerInterface {
        $entityManager = $this->createMock(
            EntityManagerInterface::class
        );

        $entityManager
            ->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(
                static function (
                    callable $operation,
                ): mixed {
                    return $operation();
                }
            );

        return $entityManager;
    }
}
