<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Service\AnnulationCombatEnLigneService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;

final class AnnulationCombatEnLigneServiceTest extends TestCase
{
    public function testLeCreateurAnnuleSonCombatEnAttente(): void
    {
        $joueur = new User();
        $combat = new Combat($joueur);
        $service = $this->creerService($combat);

        $resultat = $service->annuler(42, $joueur);

        self::assertSame($combat, $resultat);
        self::assertTrue($combat->estAnnule());
        self::assertNull($combat->getJoueur2());
        self::assertNull($combat->getGagnant());
        self::assertSame(1, $combat->getNumeroRound());
    }

    public function testRefuseUnJoueurQuiNestPasLeCreateur(): void
    {
        $combat = new Combat(new User());
        $service = $this->creerService($combat);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Seul le créateur peut annuler ce combat.'
        );

        $service->annuler(42, new User());
    }

    public function testRefuseUnCombatQuiNestPlusEnAttente(): void
    {
        $joueur = new User();
        $combat = new Combat($joueur);
        $combat->setStatut(Combat::STATUT_EN_COURS);
        $service = $this->creerService($combat);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Seul un combat en attente peut être annulé.'
        );

        $service->annuler(42, $joueur);
    }

    public function testRefuseUnCombatDejaRejoint(): void
    {
        $joueur = new User();
        $combat = new Combat($joueur);
        $combat->setJoueur2(new User());
        $service = $this->creerService($combat);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Un combat rejoint ne peut plus être annulé.'
        );

        $service->annuler(42, $joueur);
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

        $service = new AnnulationCombatEnLigneService(
            $entityManager,
            $combatRepository,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Le combat demandé est introuvable.'
        );

        $service->annuler(404, new User());
    }

    private function creerService(
        Combat $combat,
    ): AnnulationCombatEnLigneService {
        $combatRepository = $this->createStub(
            CombatRepository::class
        );
        $combatRepository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn($combat);

        return new AnnulationCombatEnLigneService(
            $this->creerEntityManager(),
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
                static fn (callable $operation): mixed => $operation()
            );

        return $entityManager;
    }
}
