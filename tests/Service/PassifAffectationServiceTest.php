<?php

namespace App\Tests\Service;

use App\Entity\Passif;
use App\Entity\Stickman;
use App\Service\PassifAffectationService;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class PassifAffectationServiceTest extends TestCase
{
    public function testConstruitUnSnapshotAvecLaPuissanceEtSixPassifsMaximum(): void
    {
        $service = new PassifAffectationService();
        $passifs = [];
        for ($i = 1; $i <= 7; ++$i) {
            $passif = (new Passif())
                ->setNom('Passif '.$i)
                ->setSlug('passif-'.$i)
                ->setDescription('Description')
                ->setType('rage')
                ->setValeur(5)
                ->setPuissance($i);
            (new ReflectionProperty(Passif::class, 'id'))->setValue($passif, $i);
            $passifs[] = $passif;
        }

        $snapshots = $service->snapshotsDepuis($passifs);

        self::assertCount(6, $snapshots);
        self::assertSame(1, $snapshots[0]['id']);
        self::assertSame(1, $snapshots[0]['puissance']);
        self::assertSame(6, $snapshots[5]['id']);
    }

    public function testSynchroniseLesCartesQuiUtilisentLePassif(): void
    {
        $passif = (new Passif())
            ->setNom('Rage')
            ->setSlug('rage')
            ->setDescription('Nouvelle description')
            ->setType('rage')
            ->setValeur(12)
            ->setPuissance(20);
        (new ReflectionProperty(Passif::class, 'id'))->setValue($passif, 7);

        $carte = (new Stickman())->setPassifs([[
            'id' => 7,
            'nom' => 'Rage',
            'description' => 'Ancienne description',
            'type' => 'rage',
            'valeur' => 5,
            'puissance' => 8,
        ]]);

        self::assertSame(1, (new PassifAffectationService())->synchroniser($passif, [$carte]));
        self::assertSame(12, $carte->getPassifs()[0]['valeur']);
        self::assertSame(20, $carte->getPassifs()[0]['puissance']);
        self::assertSame('Nouvelle description', $carte->getPassifs()[0]['description']);
    }
}
