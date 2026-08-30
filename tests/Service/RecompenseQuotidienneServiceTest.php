<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\MouvementPiecesService;
use App\Service\RecompenseQuotidienneService;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class RecompenseQuotidienneServiceTest extends TestCase
{
    public function testAccordeLaRecompenseUneFoisParJour(): void
    {
        $joueur = $this->joueurAvecId(42);
        $date = new DateTimeImmutable('2026-08-30 10:00:00');
        $service = $this->creerService($joueur);

        self::assertSame(
            RecompenseQuotidienneService::MONTANT,
            $service->reclamer($joueur, $date),
        );
        self::assertSame(1025, $joueur->getPieces());
        self::assertSame($date, $joueur->getDateDerniereRecompenseQuotidienne());
        self::assertSame(0, $service->reclamer($joueur, $date));
        self::assertSame(1025, $joueur->getPieces());
    }

    public function testAutoriseUneNouvelleRecompenseLeJourSuivant(): void
    {
        $joueur = $this->joueurAvecId(43)
            ->setDateDerniereRecompenseQuotidienne(
                new DateTimeImmutable('2026-08-30 23:59:00'),
            );
        $service = $this->creerService($joueur);

        self::assertSame(
            RecompenseQuotidienneService::MONTANT,
            $service->reclamer(
                $joueur,
                new DateTimeImmutable('2026-08-31 00:01:00'),
            ),
        );
        self::assertSame(1025, $joueur->getPieces());
    }

    public function testEnregistreUnMouvementPositif(): void
    {
        $joueur = $this->joueurAvecId(44);
        $movementEntityManager = $this->createMock(EntityManagerInterface::class);
        $movementEntityManager
            ->expects(self::once())
            ->method('persist');
        $mouvementService = new MouvementPiecesService($movementEntityManager);

        $service = $this->creerService($joueur, $mouvementService);
        $service->reclamer($joueur, new DateTimeImmutable('2026-08-30'));
    }

    private function creerService(
        User $joueur,
        ?MouvementPiecesService $mouvementService = null,
    ): RecompenseQuotidienneService {
        $repository = $this->createStub(UserRepository::class);
        $repository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn($joueur);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager
            ->method('wrapInTransaction')
            ->willReturnCallback(
                static fn (callable $operation): mixed => $operation()
            );

        return new RecompenseQuotidienneService(
            $repository,
            $entityManager,
            $mouvementService,
        );
    }

    private function joueurAvecId(int $id): User
    {
        $joueur = (new User())->setEmail('quotidien-'.$id.'@example.com');
        $reflection = new \ReflectionProperty(User::class, 'id');
        $reflection->setValue($joueur, $id);

        return $joueur;
    }
}
