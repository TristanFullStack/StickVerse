<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\User;
use App\Service\ReglesMatchmakingService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Clock\MockClock;

final class ReglesMatchmakingServiceTest extends TestCase
{
    public function testCommenceAvecDesEcartsStricts(): void
    {
        $dateCreation = new DateTimeImmutable('2026-09-01 12:00:00');
        $combat = $this->creerCombat($dateCreation);
        $service = new ReglesMatchmakingService(new MockClock($dateCreation));

        self::assertSame(
            [
                'attenteSecondes' => 0,
                'ecartEloMaximum' => 100,
                'ecartPuissanceMaximumPourcent' => 10,
            ],
            $service->criteresPour($combat),
        );
        self::assertTrue(
            $service->estCompatible($combat, 1100, 900, 1000),
        );
        self::assertFalse(
            $service->estCompatible($combat, 1101, 900, 1000),
        );
        self::assertFalse(
            $service->estCompatible($combat, 1000, 899, 1000),
        );
    }

    public function testElargitLaRechercheToutesLesTrenteSecondes(): void
    {
        $dateCreation = new DateTimeImmutable('2026-09-01 12:00:00');
        $combat = $this->creerCombat($dateCreation);
        $service = new ReglesMatchmakingService(
            new MockClock($dateCreation->modify('+90 seconds')),
        );

        self::assertSame(
            [
                'attenteSecondes' => 90,
                'ecartEloMaximum' => 250,
                'ecartPuissanceMaximumPourcent' => 25,
            ],
            $service->criteresPour($combat),
        );
        self::assertTrue(
            $service->estCompatible($combat, 1250, 750, 1000),
        );
    }

    public function testNeDepasseJamaisLesEcartsMaximums(): void
    {
        $dateCreation = new DateTimeImmutable('2026-09-01 12:00:00');
        $combat = $this->creerCombat($dateCreation);
        $service = new ReglesMatchmakingService(
            new MockClock($dateCreation->modify('+10 minutes')),
        );

        $criteres = $service->criteresPour($combat);

        self::assertSame(400, $criteres['ecartEloMaximum']);
        self::assertSame(
            40,
            $criteres['ecartPuissanceMaximumPourcent'],
        );
        self::assertFalse(
            $service->estCompatible($combat, 1401, 600, 1000),
        );
    }

    private function creerCombat(DateTimeImmutable $dateCreation): Combat
    {
        $joueur = (new User())->setElo(1000);
        $combat = new Combat($joueur);

        (new ReflectionProperty(Combat::class, 'dateCreation'))
            ->setValue($combat, $dateCreation);

        return $combat;
    }
}
