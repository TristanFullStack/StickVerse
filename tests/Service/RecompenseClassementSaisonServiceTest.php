<?php

namespace App\Tests\Service;

use App\Entity\ClassementSaisonJoueur;
use App\Entity\CollectionJeu;
use App\Entity\MouvementPieces;
use App\Entity\User;
use App\Repository\ClassementSaisonJoueurRepository;
use App\Repository\UserRepository;
use App\Service\DivisionClassementService;
use App\Service\MouvementPiecesService;
use App\Service\RecompenseClassementSaisonService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class RecompenseClassementSaisonServiceTest extends TestCase
{
    public function testAccordeUneSeuleFoisLaRecompenseDeLaDivision(): void
    {
        $joueur = $this->avecId(
            (new User())->setEmail('saison-recompense@example.com'),
            10,
        );
        $saison = $this->avecId(
            $this->creerSaison(new DateTimeImmutable('2026-08-31 23:59:59')),
            20,
        );
        $classement = (new ClassementSaisonJoueur($joueur, $saison))
            ->enregistrerResultat(200, 1.0);
        $date = new DateTimeImmutable('2026-09-01 10:00:00');

        $mouvementEntityManager = $this->createMock(EntityManagerInterface::class);
        $mouvementEntityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(
                static fn (object $mouvement): bool => $mouvement instanceof MouvementPieces
                    && $mouvement->getType() === MouvementPieces::TYPE_RECOMPENSE_SAISON
                    && $mouvement->getMontant() === 350,
            ));
        $service = $this->creerService(
            $joueur,
            $classement,
            new MouvementPiecesService($mouvementEntityManager),
        );

        self::assertSame(350, $service->reclamer($joueur, $saison, $date));
        self::assertSame(1350, $joueur->getPieces());
        self::assertTrue($classement->estRecompenseReclamee());
        self::assertSame($date, $classement->getDateRecompenseReclamee());
        self::assertSame(0, $service->reclamer($joueur, $saison, $date));
        self::assertSame(1350, $joueur->getPieces());
    }

    public function testRefuseLaRecompenseAvantLaFinDeLaSaison(): void
    {
        $joueur = $this->avecId(
            (new User())->setEmail('saison-active@example.com'),
            11,
        );
        $saison = $this->avecId(
            $this->creerSaison(new DateTimeImmutable('2026-09-30 23:59:59')),
            21,
        );
        $classement = new ClassementSaisonJoueur($joueur, $saison);

        self::assertSame(
            0,
            $this->creerService($joueur, $classement)->reclamer(
                $joueur,
                $saison,
                new DateTimeImmutable('2026-09-01'),
            ),
        );
        self::assertFalse($classement->estRecompenseReclamee());
        self::assertSame(1000, $joueur->getPieces());
    }

    private function creerService(
        User $joueur,
        ClassementSaisonJoueur $classement,
        ?MouvementPiecesService $mouvementService = null,
    ): RecompenseClassementSaisonService {
        $classementRepository = $this->createStub(
            ClassementSaisonJoueurRepository::class,
        );
        $classementRepository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn($classement);
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn($joueur);
        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager
            ->method('wrapInTransaction')
            ->willReturnCallback(
                static fn (callable $operation): mixed => $operation(),
            );

        return new RecompenseClassementSaisonService(
            $classementRepository,
            $userRepository,
            new DivisionClassementService(),
            $entityManager,
            $mouvementService,
        );
    }

    private function creerSaison(DateTimeImmutable $dateFin): CollectionJeu
    {
        return (new CollectionJeu())
            ->setNom('Saison de test')
            ->setSlug('saison-recompense-'.bin2hex(random_bytes(3)))
            ->setDescription('Saison utilisée pour tester les récompenses.')
            ->setSaison(1)
            ->setDateFin($dateFin);
    }

    private function avecId(object $entite, int $id): object
    {
        (new ReflectionProperty($entite::class, 'id'))->setValue($entite, $id);

        return $entite;
    }
}
