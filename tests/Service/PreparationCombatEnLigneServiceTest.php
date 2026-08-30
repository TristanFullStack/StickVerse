<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Service\PreparationCombatEnLigneService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class PreparationCombatEnLigneServiceTest extends TestCase
{
    public function testConfirmeLaPreparationSousVerrou(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();
        $combat = (new Combat($joueur1))
            ->setJoueur2($joueur2)
            ->setStatut(Combat::STATUT_EN_COURS)
            ->initialiserPreparation();

        $entityManager = $this->createMock(
            EntityManagerInterface::class
        );
        $entityManager
            ->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(
                static fn (callable $operation): mixed => $operation()
            );

        $combatRepository = $this->createMock(
            CombatRepository::class
        );
        $combatRepository
            ->expects(self::once())
            ->method('trouverAvecVerrouEcriture')
            ->with(42)
            ->willReturn($combat);

        $service = new PreparationCombatEnLigneService(
            $entityManager,
            $combatRepository,
            new MockClock($combat->getDateMiseAJour()),
        );

        $combatPrepare = $service->confirmer(42, $joueur1);

        self::assertSame($combat, $combatPrepare);
        self::assertTrue($combatPrepare->estPret($joueur1));
        self::assertFalse($combatPrepare->estPret($joueur2));
        self::assertTrue($combatPrepare->estEnPreparation());
    }

    public function testRefuseUneConfirmationApresCinqMinutes(): void
    {
        $joueur1 = new User();
        $combat = (new Combat($joueur1))
            ->setJoueur2(new User())
            ->setStatut(Combat::STATUT_EN_COURS)
            ->initialiserPreparation();
        $horloge = new MockClock($combat->getDateMiseAJour());
        $horloge->modify('+5 minutes');

        $entityManager = $this->createStub(
            EntityManagerInterface::class
        );
        $entityManager
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

        $service = new PreparationCombatEnLigneService(
            $entityManager,
            $combatRepository,
            $horloge,
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Le délai de préparation du combat est expiré.'
        );

        $service->confirmer(42, $joueur1);
    }
}
