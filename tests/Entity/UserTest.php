<?php

namespace App\Tests\Entity;

use App\Entity\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UserTest extends TestCase
{
    public function testCommenceAvecMillePieces(): void
    {
        self::assertSame(User::PIECES_DEPART, (new User())->getPieces());
    }

    public function testCommenceAvecCinqCentsPointsElo(): void
    {
        self::assertSame(User::ELO_DEPART, (new User())->getElo());
    }

    public function testCrediteEtDebiteLePortefeuille(): void
    {
        $joueur = new User();

        $joueur->crediterPieces(200);

        self::assertSame(1200, $joueur->getPieces());
        self::assertTrue($joueur->debiterPieces(350));
        self::assertSame(850, $joueur->getPieces());
    }

    public function testRefuseUnDebitSuperieurAuSolde(): void
    {
        $joueur = new User();

        self::assertFalse($joueur->debiterPieces(1001));
        self::assertSame(1000, $joueur->getPieces());
    }

    public function testRefuseLesMontantsInvalides(): void
    {
        $joueur = new User();

        try {
            $joueur->crediterPieces(0);
            self::fail('Un crédit nul aurait dû être refusé.');
        } catch (InvalidArgumentException) {
            self::assertSame(1000, $joueur->getPieces());
        }

        $this->expectException(InvalidArgumentException::class);
        $joueur->debiterPieces(-1);
    }

    public function testConfirmationEmailGenerePuisEffaceLeJeton(): void
    {
        $joueur = new User();
        $expiration = new \DateTimeImmutable('+1 day');

        $joueur->preparerVerificationEmail('jeton-test', $expiration);

        self::assertFalse($joueur->isEmailVerifie());
        self::assertSame(
            hash('sha256', 'jeton-test'),
            $joueur->getJetonVerificationEmailHash(),
        );
        self::assertSame($expiration, $joueur->getDateExpirationVerificationEmail());

        $joueur->confirmerEmail();

        self::assertTrue($joueur->isEmailVerifie());
        self::assertNull($joueur->getJetonVerificationEmailHash());
        self::assertNull($joueur->getDateExpirationVerificationEmail());
    }
}
