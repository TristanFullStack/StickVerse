<?php

namespace App\Tests\Entity;

use App\Entity\ReinitialisationMotDePasse;
use App\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class ReinitialisationMotDePasseTest extends TestCase
{
    public function testLeJetonEstStockeSousFormeDeHash(): void
    {
        $jeton = 'jeton-secret-de-test';
        $demande = new ReinitialisationMotDePasse(
            new User(),
            $jeton,
        );

        self::assertSame(
            hash('sha256', $jeton),
            $demande->getJetonHash(),
        );
        self::assertNotSame($jeton, $demande->getJetonHash());
    }

    public function testLeJetonExpireApresUneHeure(): void
    {
        $creation = new DateTimeImmutable('2026-08-30 12:00:00');
        $demande = new ReinitialisationMotDePasse(
            new User(),
            'jeton',
            $creation,
        );

        self::assertTrue(
            $demande->estValide($creation->modify('+59 minutes'))
        );
        self::assertFalse(
            $demande->estValide($creation->modify('+60 minutes'))
        );
    }

    public function testUnJetonUtiliseNePeutPlusServir(): void
    {
        $creation = new DateTimeImmutable('2026-08-30 12:00:00');
        $demande = new ReinitialisationMotDePasse(
            new User(),
            'jeton',
            $creation,
        );

        $demande->utiliser($creation->modify('+10 minutes'));

        self::assertFalse(
            $demande->estValide($creation->modify('+11 minutes'))
        );
        self::assertSame(
            '2026-08-30 12:10:00',
            $demande->getDateUtilisation()?->format('Y-m-d H:i:s'),
        );
    }
}
