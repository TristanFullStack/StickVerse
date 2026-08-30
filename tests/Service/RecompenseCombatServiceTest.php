<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\MouvementPieces;
use App\Entity\User;
use App\Service\MouvementPiecesService;
use App\Service\ClassementEloService;
use App\Service\RecompenseCombatService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;

final class RecompenseCombatServiceTest extends TestCase
{
    public function testRecompenseUneVictoireEtUneDefaite(): void
    {
        [$combat, $joueur1, $joueur2] = $this->creerCombat();
        $combat
            ->setGagnant($joueur1)
            ->setStatut(Combat::STATUT_TERMINE);

        $recompenses = (new RecompenseCombatService())
            ->attribuerSiNecessaire($combat);

        self::assertSame([
            'joueur1' => 100,
            'joueur2' => 25,
        ], $recompenses);
        self::assertSame(1100, $joueur1->getPieces());
        self::assertSame(1025, $joueur2->getPieces());
        self::assertTrue($combat->estRecompenseAttribuee());
    }

    public function testMetAJourLeClassementEloUneSeuleFois(): void
    {
        [$combat, $joueur1, $joueur2] = $this->creerCombat();
        $combat
            ->setGagnant($joueur1)
            ->setStatut(Combat::STATUT_TERMINE);
        $service = new RecompenseCombatService(
            classementEloService: new ClassementEloService(),
        );

        $service->attribuerSiNecessaire($combat);
        $secondeAttribution = $service->attribuerSiNecessaire($combat);

        self::assertSame(1016, $joueur1->getElo());
        self::assertSame(984, $joueur2->getElo());
        self::assertTrue($combat->estEloAttribuee());
        self::assertSame([0, 0], array_values($secondeAttribution));
    }

    public function testNeModifiePasLeClassementDUnCombatActif(): void
    {
        [$combat, $joueur1, $joueur2] = $this->creerCombat();
        $service = new RecompenseCombatService(
            classementEloService: new ClassementEloService(),
        );

        $service->attribuerSiNecessaire($combat);

        self::assertSame(User::ELO_DEPART, $joueur1->getElo());
        self::assertSame(User::ELO_DEPART, $joueur2->getElo());
        self::assertFalse($combat->estEloAttribuee());
    }

    public function testEnregistreLesMouvementsDesDeuxJoueurs(): void
    {
        [$combat, $joueur1, $joueur2] = $this->creerCombat();
        $combat
            ->setGagnant($joueur1)
            ->setStatut(Combat::STATUT_TERMINE);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::exactly(2))
            ->method('persist')
            ->with(self::isInstanceOf(MouvementPieces::class));
        $mouvementService = new MouvementPiecesService($entityManager);

        (new RecompenseCombatService($mouvementService))
            ->attribuerSiNecessaire($combat);
    }

    public function testRecompenseLesDeuxJoueursEnCasDeMatchNul(): void
    {
        [$combat, $joueur1, $joueur2] = $this->creerCombat();
        $combat->setStatut(Combat::STATUT_TERMINE);

        $recompenses = (new RecompenseCombatService())
            ->attribuerSiNecessaire($combat);

        self::assertSame([
            'joueur1' => 50,
            'joueur2' => 50,
        ], $recompenses);
        self::assertSame(1050, $joueur1->getPieces());
        self::assertSame(1050, $joueur2->getPieces());
    }

    public function testLeForfaitRecompenseUniquementLeGagnant(): void
    {
        [$combat, $joueur1, $joueur2] = $this->creerCombat();
        $combat
            ->setGagnant($joueur2)
            ->setStatut(Combat::STATUT_FORFAIT);

        (new RecompenseCombatService())->attribuerSiNecessaire($combat);

        self::assertSame(1000, $joueur1->getPieces());
        self::assertSame(1100, $joueur2->getPieces());
    }

    public function testUneDeuxiemeAttributionNeCrediteRien(): void
    {
        [$combat, $joueur1, $joueur2] = $this->creerCombat();
        $combat
            ->setGagnant($joueur1)
            ->setStatut(Combat::STATUT_TERMINE);
        $service = new RecompenseCombatService();

        $service->attribuerSiNecessaire($combat);
        $secondeAttribution = $service->attribuerSiNecessaire($combat);

        self::assertSame([
            'joueur1' => 0,
            'joueur2' => 0,
        ], $secondeAttribution);
        self::assertSame(1100, $joueur1->getPieces());
        self::assertSame(1025, $joueur2->getPieces());
    }

    public function testUnCombatActifNeDistribuePasDePieces(): void
    {
        [$combat, $joueur1, $joueur2] = $this->creerCombat();

        self::assertSame([
            'joueur1' => 0,
            'joueur2' => 0,
        ], (new RecompenseCombatService())
            ->attribuerSiNecessaire($combat));
        self::assertSame(1000, $joueur1->getPieces());
        self::assertSame(1000, $joueur2->getPieces());
    }

    public function testUnForfaitSansGagnantEstRefuse(): void
    {
        [$combat] = $this->creerCombat();
        $combat->setStatut(Combat::STATUT_FORFAIT);

        $this->expectException(LogicException::class);

        (new RecompenseCombatService())->attribuerSiNecessaire($combat);
    }

    /**
     * @return array{Combat, User, User}
     */
    private function creerCombat(): array
    {
        $joueur1 = new User();
        $joueur2 = new User();
        $combat = (new Combat($joueur1))
            ->setJoueur2($joueur2)
            ->setStatut(Combat::STATUT_EN_COURS);

        return [$combat, $joueur1, $joueur2];
    }
}
