<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\PlanRoundCombat;
use App\Entity\User;
use App\Model\PlanCombat;
use App\Repository\CombatRepository;
use App\Repository\PlanRoundCombatRepository;
use App\Service\ExpirationPlanCombatEnLigneService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class ExpirationPlanCombatEnLigneServiceTest extends TestCase
{
    public function testConserveLeCombatAvantCinqMinutes(): void
    {
        [$combat, $plan] = $this->creerCombatAvecPremierPlan();
        $horloge = new MockClock($plan->getDateSoumission());
        $horloge->modify('+4 minutes 59 seconds');

        $expiration = $this->creerService(
            $combat,
            [$plan],
            $horloge,
        )->expirerSiNecessaire(42);

        self::assertFalse($expiration);
        self::assertTrue($combat->estEnCours());
        self::assertNull($combat->getGagnant());
    }

    public function testDeclareLeJoueurPretGagnantApresCinqMinutes(): void
    {
        [$combat, $plan] = $this->creerCombatAvecPremierPlan();
        $horloge = new MockClock($plan->getDateSoumission());
        $horloge->modify('+5 minutes');

        $expiration = $this->creerService(
            $combat,
            [$plan],
            $horloge,
        )->expirerSiNecessaire(42);

        self::assertTrue($expiration);
        self::assertTrue($combat->estForfait());
        self::assertSame($plan->getJoueur(), $combat->getGagnant());
    }

    public function testNeDeclarePasDeForfaitSiLesDeuxPlansSontPresents(): void
    {
        [$combat, $planJoueur1] = $this->creerCombatAvecPremierPlan();
        $joueur2 = $combat->getJoueur2();
        self::assertInstanceOf(User::class, $joueur2);

        $planJoueur2 = new PlanRoundCombat(
            $combat,
            $joueur2,
            $this->creerPlan(),
        );

        $horloge = new MockClock($planJoueur1->getDateSoumission());
        $horloge->modify('+10 minutes');

        $expiration = $this->creerService(
            $combat,
            [$planJoueur1, $planJoueur2],
            $horloge,
        )->expirerSiNecessaire(42);

        self::assertFalse($expiration);
        self::assertTrue($combat->estEnCours());
        self::assertNull($combat->getGagnant());
    }

    public function testNeDeclarePasDeGagnantSansPlan(): void
    {
        $combat = new Combat(new User());
        $combat->setJoueur2(new User());
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $expiration = $this->creerService(
            $combat,
            [],
            new MockClock('+10 minutes'),
        )->expirerSiNecessaire(42);

        self::assertFalse($expiration);
        self::assertTrue($combat->estEnCours());
        self::assertNull($combat->getGagnant());
    }

    /**
     * @return array{Combat, PlanRoundCombat}
     */
    private function creerCombatAvecPremierPlan(): array
    {
        $joueur1 = new User();
        $combat = new Combat($joueur1);
        $combat->setJoueur2(new User());
        $combat->setStatut(Combat::STATUT_EN_COURS);

        return [
            $combat,
            new PlanRoundCombat(
                $combat,
                $joueur1,
                $this->creerPlan(),
            ),
        ];
    }

    private function creerPlan(): PlanCombat
    {
        return new PlanCombat('A', 'B', 'C', 'D');
    }

    /**
     * @param list<PlanRoundCombat> $plans
     */
    private function creerService(
        Combat $combat,
        array $plans,
        MockClock $horloge,
    ): ExpirationPlanCombatEnLigneService {
        $entityManager = $this->createMock(
            EntityManagerInterface::class
        );
        $entityManager
            ->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(
                static fn (callable $operation): mixed => $operation()
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
            ->willReturn($plans);

        return new ExpirationPlanCombatEnLigneService(
            $entityManager,
            $combatRepository,
            $planRepository,
            $horloge,
        );
    }
}
