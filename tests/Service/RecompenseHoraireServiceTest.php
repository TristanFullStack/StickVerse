<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MouvementPiecesService;
use App\Service\RecompenseHoraireService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class RecompenseHoraireServiceTest extends TestCase
{
    public function testCumuleVingtPiecesParHeureAvecUnPlafondDeCent(): void
    {
        $joueur = $this->joueurAvecId(1)->setDateDerniereRecompenseHoraire(
            new DateTimeImmutable('2026-09-01 08:00:00'),
        );
        $service = $this->creerService($joueur);

        self::assertSame(
            60,
            $service->montantDisponible($joueur, new DateTimeImmutable('2026-09-01 11:30:00')),
        );
        self::assertSame(
            100,
            $service->montantDisponible($joueur, new DateTimeImmutable('2026-09-01 20:00:00')),
        );
    }

    public function testReclameLeMontantDisponibleEtConserveLesMinutes(): void
    {
        $joueur = $this->joueurAvecId(2)->setDateDerniereRecompenseHoraire(
            new DateTimeImmutable('2026-09-01 08:00:00'),
        );
        $service = $this->creerService($joueur);

        self::assertSame(
            40,
            $service->reclamer($joueur, new DateTimeImmutable('2026-09-01 10:30:00')),
        );
        self::assertEquals(
            new DateTimeImmutable('2026-09-01 10:00:00'),
            $joueur->getDateDerniereRecompenseHoraire(),
        );
        self::assertSame(1040, $joueur->getPieces());
    }

    private function creerService(
        User $joueur,
        ?MouvementPiecesService $mouvementService = null,
    ): RecompenseHoraireService {
        $repository = $this->createStub(UserRepository::class);
        $repository->method('trouverAvecVerrouEcriture')->willReturn($joueur);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('wrapInTransaction')->willReturnCallback(
            static fn (callable $operation): mixed => $operation(),
        );

        return new RecompenseHoraireService($repository, $entityManager, $mouvementService);
    }

    private function joueurAvecId(int $id): User
    {
        $joueur = (new User())->setEmail('horaire-'.$id.'@example.com');
        (new \ReflectionProperty(User::class, 'id'))->setValue($joueur, $id);

        return $joueur;
    }
}
