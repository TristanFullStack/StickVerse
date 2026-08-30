<?php

namespace App\Tests\Entity;

use App\Entity\MouvementPieces;
use App\Entity\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MouvementPiecesTest extends TestCase
{
    public function testConstruitUnMouvementAvecSonMontantSigne(): void
    {
        $joueur = new User();
        $mouvement = new MouvementPieces(
            $joueur,
            -120,
            MouvementPieces::TYPE_ACHAT_CAISSE,
            'Ouverture de la caisse Origine',
        );

        self::assertSame($joueur, $mouvement->getUtilisateur());
        self::assertSame(-120, $mouvement->getMontant());
        self::assertSame(
            MouvementPieces::TYPE_ACHAT_CAISSE,
            $mouvement->getType(),
        );
        self::assertSame(
            'Ouverture de la caisse Origine',
            $mouvement->getLibelle(),
        );
    }

    public function testRefuseUnMontantNul(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MouvementPieces(
            new User(),
            0,
            MouvementPieces::TYPE_RECOMPENSE_COMBAT,
            'Récompense',
        );
    }

    public function testRefuseUnTypeInconnu(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new MouvementPieces(
            new User(),
            10,
            'type_inconnu',
            'Test',
        );
    }
}
