<?php

namespace App\Tests\Service;

use App\Entity\Stickman;
use App\Service\CombatService;
use PHPUnit\Framework\TestCase;

final class CombatServiceTest extends TestCase
{
    public function testCalculeLaPressionDattaqueCommeDansLaV24(): void
    {
        $service = new CombatService();

        self::assertSame(0, $service->bonusPressionAttaque(1));
        self::assertSame(0, $service->bonusPressionAttaque(3));
        self::assertSame(10, $service->bonusPressionAttaque(4));
        self::assertSame(20, $service->bonusPressionAttaque(7));
        self::assertSame(30, $service->bonusPressionAttaque(10));
        self::assertSame(40, $service->bonusPressionAttaque(11));
        self::assertSame(90, $service->bonusPressionAttaque(16));
    }

    public function testAppliqueLaPressionAuxAttaquesMaisPasAuxDefenses(): void
    {
        $stickman = (new Stickman())
            ->setAttaque(55)
            ->setDefense(70);
        $service = new CombatService();

        self::assertSame(
            72,
            $service->calculerAttaqueTotale([$stickman], 10),
        );
        self::assertSame(
            70,
            $service->calculerDefenseTotale([$stickman]),
        );
    }

    public function testCalculerUnImpactNormal(): void
    {
        $service = new CombatService();

        $resultat = $service->calculerImpact(
            attaqueTotale: 8,
            defenseTotale: 3,
            pvActuels: 10,
        );

        self::assertSame(5, $resultat['degatsCalcules']);
        self::assertSame(5, $resultat['degatsEffectifs']);
        self::assertSame(5, $resultat['pvRestants']);
        self::assertSame(0, $resultat['overkill']);
    }

    public function testLaDefensePeutBloquerTousLesDegats(): void
    {
        $service = new CombatService();

        $resultat = $service->calculerImpact(
            attaqueTotale: 4,
            defenseTotale: 7,
            pvActuels: 10,
        );

        self::assertSame(0, $resultat['degatsCalcules']);
        self::assertSame(10, $resultat['pvRestants']);
    }

    public function testLesDegatsNeDepassentPasLesPvActuels(): void
    {
        $service = new CombatService();

        $resultat = $service->calculerImpact(
            attaqueTotale: 15,
            defenseTotale: 2,
            pvActuels: 5,
        );

        self::assertSame(13, $resultat['degatsCalcules']);
        self::assertSame(5, $resultat['degatsEffectifs']);
        self::assertSame(8, $resultat['overkill']);
        self::assertSame(0, $resultat['pvRestants']);
    }

    public function testCalculerLesPuissancesTotalesDunDuo(): void
    {
        $guerrier = new Stickman();
        $guerrier->setAttaque(2);
        $guerrier->setDefense(2);

        $archer = new Stickman();
        $archer->setAttaque(4);
        $archer->setDefense(1);

        $service = new CombatService();

        $stickmenEquipeX = [
            $guerrier,
            $archer,
        ];

        self::assertSame(
            6,
            $service->calculerAttaqueTotale($stickmenEquipeX),
        );

        self::assertSame(
            3,
            $service->calculerDefenseTotale($stickmenEquipeX),
        );
    }

    public function testResoudreUnFocusContreUneDefenseSimple(): void
    {
        $attaquantA = new Stickman();
        $attaquantA->setAttaque(2);

        $attaquantB = new Stickman();
        $attaquantB->setAttaque(4);

        $attaquantC = new Stickman();
        $attaquantC->setAttaque(2);

        $attaquantD = new Stickman();
        $attaquantD->setAttaque(2);

        $defenseurA = new Stickman();
        $defenseurA->setDefense(2);

        $defenseurB = new Stickman();
        $defenseurB->setDefense(1);

        $service = new CombatService();

        $resultat = $service->resoudreCible(
            attaquants: [
                $attaquantA,
                $attaquantB,
                $attaquantC,
                $attaquantD,
            ],
            defenseurs: [
                $defenseurA,
                $defenseurB,
            ],
            pvActuels: 8,
        );

        self::assertSame(10, $resultat['attaque']);
        self::assertSame(3, $resultat['defense']);
        self::assertSame(7, $resultat['degatsCalcules']);
        self::assertSame(1, $resultat['pvRestants']);
    }

    public function testDeuxStickmansPeuventEtreKoSimultanement(): void
    {
        $attaqueJoueur = new Stickman();
        $attaqueJoueur->setAttaque(10);

        $attaqueAdversaire = new Stickman();
        $attaqueAdversaire->setAttaque(10);

        $service = new CombatService();

        $resultats = $service->resoudreRound([
            'joueur_A' => [
                'attaquants' => [$attaqueAdversaire],
                'defenseurs' => [],
                'pvActuels' => 5,
            ],
            'adversaire_A' => [
                'attaquants' => [$attaqueJoueur],
                'defenseurs' => [],
                'pvActuels' => 5,
            ],
        ]);

        self::assertSame(5, $resultats['joueur_A']['pvAvant']);
        self::assertSame(0, $resultats['joueur_A']['pvRestants']);

        self::assertSame(5, $resultats['adversaire_A']['pvAvant']);
        self::assertSame(0, $resultats['adversaire_A']['pvRestants']);
    }

}
