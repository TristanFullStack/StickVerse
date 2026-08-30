<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Service\ExpirationPreparationCombatEnLigneService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

final class ExpirationPreparationCombatEnLigneServiceTest extends TestCase
{
    public function testConserveLaPreparationAvantCinqMinutes(): void
    {
        [$combat] = $this->creerCombatEnPreparation();
        $horloge = new MockClock($combat->getDateMiseAJour());
        $horloge->modify('+4 minutes 59 seconds');

        $resultat = $this->creerService($combat, $horloge)
            ->expirerSiNecessaire(42);

        self::assertNull($resultat);
        self::assertTrue($combat->estEnPreparation());
        self::assertNull($combat->getGagnant());
    }

    public function testAnnuleSiAucunJoueurNestPret(): void
    {
        [$combat] = $this->creerCombatEnPreparation();
        $horloge = new MockClock($combat->getDateMiseAJour());
        $horloge->modify('+5 minutes');

        $resultat = $this->creerService($combat, $horloge)
            ->expirerSiNecessaire(42);

        self::assertSame(
            ExpirationPreparationCombatEnLigneService::RESULTAT_ANNULE,
            $resultat,
        );
        self::assertTrue($combat->estAnnule());
        self::assertNull($combat->getGagnant());
    }

    public function testDeclareLeSeulJoueurPretGagnantParForfait(): void
    {
        [$combat, $joueur1] = $this->creerCombatEnPreparation();
        $combat->confirmerPret($joueur1);
        $horloge = new MockClock($combat->getDateMiseAJour());
        $horloge->modify('+5 minutes');

        $resultat = $this->creerService($combat, $horloge)
            ->expirerSiNecessaire(42);

        self::assertSame(
            ExpirationPreparationCombatEnLigneService::RESULTAT_FORFAIT,
            $resultat,
        );
        self::assertTrue($combat->estForfait());
        self::assertSame($joueur1, $combat->getGagnant());
    }

    public function testNeModifiePasUnCombatDejaPret(): void
    {
        [$combat, $joueur1, $joueur2] =
            $this->creerCombatEnPreparation();
        $combat->confirmerPret($joueur1);
        $combat->confirmerPret($joueur2);
        $horloge = new MockClock($combat->getDateMiseAJour());
        $horloge->modify('+10 minutes');

        $resultat = $this->creerService($combat, $horloge)
            ->expirerSiNecessaire(42);

        self::assertNull($resultat);
        self::assertTrue($combat->estEnCours());
        self::assertTrue($combat->estPretAJouer());
    }

    /**
     * @return array{Combat, User, User}
     */
    private function creerCombatEnPreparation(): array
    {
        $joueur1 = new User();
        $joueur2 = new User();
        $combat = (new Combat($joueur1))
            ->setJoueur2($joueur2)
            ->setStatut(Combat::STATUT_EN_COURS)
            ->initialiserPreparation();

        return [$combat, $joueur1, $joueur2];
    }

    private function creerService(
        Combat $combat,
        MockClock $horloge,
    ): ExpirationPreparationCombatEnLigneService {
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

        return new ExpirationPreparationCombatEnLigneService(
            $entityManager,
            $combatRepository,
            $horloge,
        );
    }
}
