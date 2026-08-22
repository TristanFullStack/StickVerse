<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Service\AbandonCombatService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;

final class AbandonCombatServiceTest extends TestCase
{
    public function testLeJoueur1AbandonneEtLeJoueur2Gagne(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $service = $this->creerService($combat);

        $resultat = $service->abandonner(
            combatId: 42,
            joueur: $joueur1,
        );

        self::assertSame($combat, $resultat);

        self::assertSame(
            Combat::STATUT_ABANDONNE,
            $combat->getStatut(),
        );

        self::assertSame(
            $joueur2,
            $combat->getGagnant(),
        );

        self::assertSame(1, $combat->getNumeroRound());
    }

    public function testLeJoueur2AbandonneEtLeJoueur1Gagne(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $service = $this->creerService($combat);

        $service->abandonner(
            combatId: 42,
            joueur: $joueur2,
        );

        self::assertSame(
            Combat::STATUT_ABANDONNE,
            $combat->getStatut(),
        );

        self::assertSame(
            $joueur1,
            $combat->getGagnant(),
        );

        self::assertSame(1, $combat->getNumeroRound());
    }

    public function testRefuseUnJoueurExterieurAuCombat(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();
        $intrus = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $service = $this->creerService($combat);

        $this->expectException(LogicException::class);

        $this->expectExceptionMessage(
            'Seul un participant peut abandonner ce combat.'
        );

        $service->abandonner(
            combatId: 42,
            joueur: $intrus,
        );
    }

    public function testRefuseUnCombatQuiNestPasEnCours(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);

        $service = $this->creerService($combat);

        $this->expectException(LogicException::class);

        $this->expectExceptionMessage(
            'Seul un combat en cours peut être abandonné.'
        );

        $service->abandonner(
            combatId: 42,
            joueur: $joueur1,
        );
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

        $service = new AbandonCombatService(
            $entityManager,
            $combatRepository,
        );

        $this->expectException(LogicException::class);

        $this->expectExceptionMessage(
            'Le combat demandé est introuvable.'
        );

        $service->abandonner(
            combatId: 404,
            joueur: new User(),
        );
    }

    private function creerService(
        Combat $combat,
    ): AbandonCombatService {
        $entityManager = $this->creerEntityManager();

        $combatRepository = $this->createStub(
            CombatRepository::class
        );

        $combatRepository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn($combat);

        return new AbandonCombatService(
            $entityManager,
            $combatRepository,
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
                static function (callable $operation): mixed {
                    return $operation();
                }
            );

        return $entityManager;
    }
}