<?php

namespace App\Tests\Service;

use App\Entity\Inventaire;
use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\StickmanRepository;
use App\Service\InitialisationNouveauJoueurService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;

final class InitialisationNouveauJoueurServiceTest extends TestCase
{
    public function testAttribueQuatreStickmansDifferentsDansUnOrdreStable(): void
    {
        $stickmans = array_map(
            fn (string $slug): Stickman => $this->creerStickman($slug),
            InitialisationNouveauJoueurService::STICKMANS_DEPART,
        );

        $repository = $this->createStub(StickmanRepository::class);
        $repository->method('findBy')->willReturn(array_reverse($stickmans));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::exactly(4))
            ->method('persist')
            ->with(self::isInstanceOf(Inventaire::class));

        $utilisateur = (new User())
            ->setEmail('nouveau@example.com')
            ->setPassword('mot-de-passe-test');

        $pack = (new InitialisationNouveauJoueurService(
            $repository,
            $entityManager,
        ))->initialiser($utilisateur);

        self::assertSame(
            InitialisationNouveauJoueurService::STICKMANS_DEPART,
            array_map(
                static fn (Stickman $stickman): ?string => $stickman->getSlug(),
                $pack,
            ),
        );
        self::assertCount(4, $utilisateur->getInventaires());
    }

    public function testResteIdempotentPourUnJoueurDejaInitialise(): void
    {
        $stickmans = array_map(
            fn (string $slug): Stickman => $this->creerStickman($slug),
            InitialisationNouveauJoueurService::STICKMANS_DEPART,
        );

        $utilisateur = (new User())
            ->setEmail('existant@example.com')
            ->setPassword('mot-de-passe-test');

        foreach ($stickmans as $stickman) {
            $utilisateur->addInventaire(
                (new Inventaire())->setStickman($stickman),
            );
        }

        $repository = $this->createStub(StickmanRepository::class);
        $repository->method('findBy')->willReturn($stickmans);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        (new InitialisationNouveauJoueurService(
            $repository,
            $entityManager,
        ))->initialiser($utilisateur);

        self::assertCount(4, $utilisateur->getInventaires());
    }

    public function testRefuseUnCatalogueSansLesQuatreStickmansActifs(): void
    {
        $repository = $this->createStub(StickmanRepository::class);
        $repository->method('findBy')->willReturn([
            $this->creerStickman('guerrier'),
        ]);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Stickmans actifs manquants : archer, lancier, tank.'
        );

        (new InitialisationNouveauJoueurService(
            $repository,
            $entityManager,
        ))->initialiser(new User());
    }

    private function creerStickman(string $slug): Stickman
    {
        return (new Stickman())
            ->setSlug($slug)
            ->setNom(ucfirst($slug))
            ->setDescription('Stickman de départ.')
            ->setImage($slug.'.png')
            ->setRarete(1)
            ->setPv(10)
            ->setAttaque(2)
            ->setDefense(2)
            ->setStatutActif(true);
    }
}
