<?php

namespace App\Tests\Entity;

use App\Entity\Combat;
use App\Entity\ResultatRoundCombat;
use App\Entity\User;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ResultatRoundCombatTest extends TestCase
{
    public function testCreeUnResultatHistoriquePourUnRound(): void
    {
        $combat = new Combat(new User());
        $resultats = [
            'joueur1_A' => [
                'attaque' => 4,
                'defense' => 2,
                'degatsCalcules' => 2,
                'degatsEffectifs' => 2,
                'overkill' => 0,
                'pvAvant' => 5,
                'pvRestants' => 3,
            ],
        ];

        $avantCreation = new DateTimeImmutable();
        $resultatRound = new ResultatRoundCombat(
            $combat,
            1,
            $resultats,
        );

        self::assertNull($resultatRound->getId());
        self::assertSame($combat, $resultatRound->getCombat());
        self::assertSame(1, $resultatRound->getNumeroRound());
        self::assertSame($resultats, $resultatRound->getResultats());
        self::assertGreaterThanOrEqual(
            $avantCreation,
            $resultatRound->getDateResolution(),
        );
        self::assertCount(1, $combat->getResultatsRounds());
        self::assertTrue(
            $combat->getResultatsRounds()->contains($resultatRound)
        );
    }

    public function testRefuseUnNumeroDeRoundInvalide(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le numéro du round résolu doit être supérieur à 0.'
        );

        new ResultatRoundCombat(
            new Combat(new User()),
            0,
            [
                'joueur1_A' => [
                    'pvRestants' => 5,
                ],
            ],
        );
    }

    public function testRefuseUnResultatVide(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Le résultat du round ne peut pas être vide.'
        );

        new ResultatRoundCombat(
            new Combat(new User()),
            1,
            [],
        );
    }
}
