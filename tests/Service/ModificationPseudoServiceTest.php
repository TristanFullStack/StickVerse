<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ModificationPseudoService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class ModificationPseudoServiceTest extends TestCase
{
    public function testModifieUnPseudoDisponible(): void
    {
        $joueur = (new User())->setPseudo('AncienPseudo');
        $repository = $this->createMock(UserRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['pseudo' => 'NouveauPseudo'])
            ->willReturn(null);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $service = new ModificationPseudoService(
            $repository,
            $entityManager,
        );

        self::assertSame(
            ModificationPseudoService::RESULTAT_OK,
            $service->modifier($joueur, ' NouveauPseudo '),
        );
        self::assertSame('NouveauPseudo', $joueur->getPseudo());
    }

    public function testRefuseLePseudoActuel(): void
    {
        $joueur = (new User())->setPseudo('PseudoActuel');
        $repository = $this->createMock(UserRepository::class);
        $repository->expects(self::never())->method('findOneBy');
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $service = new ModificationPseudoService(
            $repository,
            $entityManager,
        );

        self::assertSame(
            ModificationPseudoService::RESULTAT_IDENTIQUE,
            $service->modifier($joueur, 'pseudoactuel'),
        );
    }

    public function testRefuseUnPseudoOccupe(): void
    {
        $joueur = (new User())->setPseudo('PseudoLibre');
        $autreJoueur = (new User())->setPseudo('PseudoOccupe');
        $repository = $this->createMock(UserRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn($autreJoueur);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $service = new ModificationPseudoService(
            $repository,
            $entityManager,
        );

        self::assertSame(
            ModificationPseudoService::RESULTAT_INDISPONIBLE,
            $service->modifier($joueur, 'PseudoOccupe'),
        );
        self::assertSame('PseudoLibre', $joueur->getPseudo());
    }
}
