<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\CombatRepository;
use App\Repository\MouvementPiecesRepository;
use App\Repository\UserRepository;
use App\Service\MissionsJoueurService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class MissionsJoueurServiceTest extends TestCase
{
    public function testConstruitLesMissionsQuotidiennesEtHebdomadaires(): void
    {
        $joueur = $this->joueurAvecId(1);
        $combats = $this->createStub(CombatRepository::class);
        $combats->method('compterDepuisPourJoueur')->willReturn(2);
        $combats->method('compterVictoiresDepuisPourJoueur')->willReturn(1);
        $mouvements = $this->createStub(MouvementPiecesRepository::class);
        $mouvements->method('compterDepuisPourJoueurEtType')->willReturn(3);

        $missions = $this->creerService($joueur, $combats, $mouvements)->construire(
            $joueur,
            new DateTimeImmutable('2026-09-01 12:00:00'),
        );

        self::assertCount(2, $missions['quotidiennes']);
        self::assertCount(3, $missions['hebdomadaires']);
        self::assertTrue($missions['quotidiennes'][0]['disponible']);
        self::assertTrue($missions['hebdomadaires'][2]['disponible']);
    }

    public function testReclameUneMissionUneSeuleFoisParPeriode(): void
    {
        $joueur = $this->joueurAvecId(2);
        $combats = $this->createStub(CombatRepository::class);
        $combats->method('compterDepuisPourJoueur')->willReturn(1);
        $combats->method('compterVictoiresDepuisPourJoueur')->willReturn(0);
        $mouvements = $this->createStub(MouvementPiecesRepository::class);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')->willReturnCallback(
            static fn (callable $operation): mixed => $operation(),
        );
        $users = $this->createStub(UserRepository::class);
        $users->method('trouverAvecVerrouEcriture')->willReturn($joueur);

        $service = new MissionsJoueurService($combats, $mouvements, $users, $entityManager);
        $date = new DateTimeImmutable('2026-09-01 12:00:00');

        self::assertSame(50, $service->reclamer($joueur, 'quotidiennes', 'combat', $date));
        self::assertSame(0, $service->reclamer($joueur, 'quotidiennes', 'combat', $date));
        self::assertSame(1050, $joueur->getPieces());
    }

    private function creerService(
        User $joueur,
        CombatRepository $combats,
        MouvementPiecesRepository $mouvements,
    ): MissionsJoueurService {
        $users = $this->createStub(UserRepository::class);
        $users->method('trouverAvecVerrouEcriture')->willReturn($joueur);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')->willReturnCallback(
            static fn (callable $operation): mixed => $operation(),
        );

        return new MissionsJoueurService($combats, $mouvements, $users, $entityManager);
    }

    private function joueurAvecId(int $id): User
    {
        $joueur = (new User())->setEmail('mission-'.$id.'@example.com');
        (new \ReflectionProperty(User::class, 'id'))->setValue($joueur, $id);

        return $joueur;
    }
}
