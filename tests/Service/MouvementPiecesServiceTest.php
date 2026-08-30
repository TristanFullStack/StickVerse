<?php

namespace App\Tests\Service;

use App\Entity\MouvementPieces;
use App\Entity\User;
use App\Service\MouvementPiecesService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class MouvementPiecesServiceTest extends TestCase
{
    public function testEnregistreLeMouvementDansDoctrine(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::isInstanceOf(MouvementPieces::class));

        $joueur = new User();
        $mouvement = (new MouvementPiecesService($entityManager))
            ->enregistrer(
                $joueur,
                100,
                MouvementPieces::TYPE_RECOMPENSE_COMBAT,
                'Récompense du combat #42',
            );

        self::assertSame(100, $mouvement->getMontant());
        self::assertTrue($joueur->getMouvementsPieces()->contains($mouvement));
    }
}
