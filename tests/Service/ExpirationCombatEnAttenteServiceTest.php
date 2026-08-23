<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Service\ExpirationCombatEnAttenteService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class ExpirationCombatEnAttenteServiceTest extends TestCase
{
    public function testConserveUnCombatAvantCinqMinutes(): void
    {
        $combat = new Combat(new User());
        $horloge = new MockClock($combat->getDateCreation());
        $horloge->modify('+4 minutes 59 seconds');

        $expiration = $this->creerService($combat, $horloge)
            ->expirerSiNecessaire(42);

        self::assertFalse($expiration);
        self::assertTrue($combat->estEnAttente());
    }

    public function testAnnuleUnCombatApresCinqMinutes(): void
    {
        $combat = new Combat(new User());
        $horloge = new MockClock($combat->getDateCreation());
        $horloge->modify('+5 minutes');

        $expiration = $this->creerService($combat, $horloge)
            ->expirerSiNecessaire(42);

        self::assertTrue($expiration);
        self::assertTrue($combat->estAnnule());
        self::assertNull($combat->getGagnant());
    }

    public function testNeTouchePasUnCombatEnCours(): void
    {
        $combat = new Combat(new User());
        $combat->setJoueur2(new User());
        $combat->setStatut(Combat::STATUT_EN_COURS);
        $horloge = new MockClock($combat->getDateCreation());
        $horloge->modify('+10 minutes');

        $expiration = $this->creerService($combat, $horloge)
            ->expirerSiNecessaire(42);

        self::assertFalse($expiration);
        self::assertTrue($combat->estEnCours());
    }

    public function testRefuseUnCombatIntrouvable(): void
    {
        $entityManager = $this->creerEntityManager();
        $combatRepository = $this->createStub(
            CombatRepository::class
        );
        $combatRepository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn(null);

        $service = new ExpirationCombatEnAttenteService(
            $entityManager,
            $combatRepository,
            new MockClock(),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Le combat demandé est introuvable.'
        );

        $service->expirerSiNecessaire(404);
    }

    private function creerService(
        Combat $combat,
        MockClock $horloge,
    ): ExpirationCombatEnAttenteService {
        $combatRepository = $this->createStub(
            CombatRepository::class
        );
        $combatRepository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn($combat);

        return new ExpirationCombatEnAttenteService(
            $this->creerEntityManager(),
            $combatRepository,
            $horloge,
        );
    }

    private function creerEntityManager(): EntityManagerInterface
    {
        $entityManager = $this->createMock(
            EntityManagerInterface::class
        );
        $entityManager
            ->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(
                static fn (callable $operation): mixed => $operation()
            );

        return $entityManager;
    }
}
