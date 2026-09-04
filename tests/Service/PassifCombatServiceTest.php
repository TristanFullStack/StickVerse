<?php

namespace App\Tests\Service;

use App\Entity\Stickman;
use App\Service\CombatService;
use App\Service\PassifCombatService;
use App\Service\ScorePuissanceService;
use PHPUnit\Framework\TestCase;

final class PassifCombatServiceTest extends TestCase
{
    public function testIgnoreLesPassifsInconnusEtPlafonneLeBonus(): void
    {
        $stickman = (new Stickman())
            ->setPv(100)
            ->setAttaque(100)
            ->setDefense(50)
            ->setPassifs([
                [
                    'nom' => 'Furie',
                    'description' => 'Augmente l’attaque.',
                    'type' => PassifCombatService::TYPE_BONUS_ATTAQUE_POURCENTAGE,
                    'valeur' => 40,
                ],
                [
                    'nom' => 'Second souffle',
                    'description' => 'Augmente encore l’attaque.',
                    'type' => PassifCombatService::TYPE_BONUS_ATTAQUE_POURCENTAGE,
                    'valeur' => 40,
                ],
                [
                    'nom' => 'Triche',
                    'description' => 'Ne doit jamais être exécuté.',
                    'type' => 'expression_php',
                    'valeur' => 999,
                ],
            ]);

        $service = new PassifCombatService();

        self::assertSame(
            50,
            $service->bonusAttaquePourcentage([$stickman], 1),
        );
        self::assertCount(
            2,
            $service->passifsActifs([$stickman], 1),
        );
    }

    public function testUnPassifPeutCommencerAUnRoundDonne(): void
    {
        $stickman = (new Stickman())
            ->setPv(100)
            ->setAttaque(100)
            ->setDefense(50)
            ->setPassifs([
                [
                    'nom' => 'Pression',
                    'description' => '+10 % ATQ à partir du round 4.',
                    'type' => PassifCombatService::TYPE_BONUS_ATTAQUE_POURCENTAGE,
                    'valeur' => 10,
                    'a_partir_round' => 4,
                ],
            ]);

        $service = new CombatService(new PassifCombatService());

        self::assertSame(100, $service->calculerAttaqueTotale([$stickman], 3));
        self::assertSame(120, $service->calculerAttaqueTotale([$stickman], 4));
    }

    public function testLeBonusDeDefenseEstPrisEnCompteDansUnImpact(): void
    {
        $attaquant = (new Stickman())
            ->setPv(100)
            ->setAttaque(100)
            ->setDefense(0);
        $defenseur = (new Stickman())
            ->setPv(100)
            ->setAttaque(0)
            ->setDefense(50)
            ->setPassifs([
                [
                    'nom' => 'Rempart',
                    'description' => '+20 % DEF.',
                    'type' => PassifCombatService::TYPE_BONUS_DEFENSE_POURCENTAGE,
                    'valeur' => 20,
                ],
            ]);

        $resultat = (new CombatService(new PassifCombatService()))
            ->resoudreCible([$attaquant], [$defenseur], 100);

        self::assertSame(60, $resultat['defense']);
        self::assertSame(40, $resultat['degatsEffectifs']);
        self::assertSame(20, $resultat['bonusPassifsDefense']);
    }

    public function testUnPassifContextuelUtiliseLesPvReelsDeLaCarte(): void
    {
        $stickman = (new Stickman())
            ->setPv(100)
            ->setAttaque(100)
            ->setDefense(0)
            ->setPassifs([[
                'nom' => 'Rage',
                'description' => '+10 % ATQ sous 40 % de PV.',
                'type' => 'rage',
                'valeur' => 10,
            ]]);

        $service = new PassifCombatService();

        self::assertSame(0, $service->bonusAttaquePourcentage([$stickman], 1));
        self::assertSame(
            10,
            $service->bonusAttaquePourcentage([$stickman], 1, [
                'attaquants' => [[
                    'stickman' => $stickman,
                    'pvActuels' => 30,
                    'pvMaximum' => 100,
                    'partenaireVivant' => true,
                    'protegeAllie' => false,
                ]],
            ]),
        );
    }

    public function testLaPrecisionReduitLaDefenseDansUnImpact(): void
    {
        $attaquant = (new Stickman())
            ->setPv(100)
            ->setAttaque(100)
            ->setDefense(0)
            ->setPassifs([[
                'nom' => 'Précision',
                'description' => 'Ignore 10 % de la défense adverse.',
                'type' => 'precision',
                'valeur' => 10,
            ]]);
        $defenseur = (new Stickman())
            ->setPv(100)
            ->setAttaque(0)
            ->setDefense(50);

        $resultat = (new CombatService(new PassifCombatService()))->resoudreCible(
            [$attaquant],
            [$defenseur],
            100,
            1,
            [
                'attaquants' => [[
                    'stickman' => $attaquant,
                    'pvActuels' => 100,
                    'pvMaximum' => 100,
                    'partenaireVivant' => true,
                    'protegeAllie' => false,
                ]],
                'defenseurs' => [[
                    'stickman' => $defenseur,
                    'pvActuels' => 100,
                    'pvMaximum' => 100,
                    'partenaireVivant' => true,
                    'protegeAllie' => false,
                ]],
                'pvActuelsCible' => 100,
                'pvMaximumCible' => 100,
            ],
        );

        self::assertSame(45, $resultat['defense']);
        self::assertSame(10, $resultat['ignoreDefensePassifs']);
    }

    public function testUnPassifContextuelAugmenteLaPuissanceDeLaCarte(): void
    {
        $stickman = (new Stickman())
            ->setPv(100)
            ->setAttaque(50)
            ->setDefense(40)
            ->setPassifs([[
                'nom' => 'Rage',
                'description' => '+10 % ATQ sous 40 % de PV.',
                'type' => 'rage',
                'valeur' => 10,
            ]]);

        self::assertSame(
            190,
            (new ScorePuissanceService(new PassifCombatService()))
                ->calculerStickman($stickman),
        );
    }

    public function testLaPuissanceManuelleDuPassifEstAjouteeUneSeuleFois(): void
    {
        $stickman = (new Stickman())
            ->setPv(100)
            ->setAttaque(50)
            ->setDefense(40)
            ->setPassifs([[
                'nom' => 'Passif central',
                'description' => 'Puissance fixe.',
                'type' => 'rage',
                'valeur' => 10,
                'puissance' => 37,
            ]]);

        // Base : 20 PV + 100 ATQ + 60 DEF, puis +37 du catalogue central.
        self::assertSame(
            217,
            (new ScorePuissanceService(new PassifCombatService()))
                ->calculerStickman($stickman),
        );
    }

    public function testLesMalusDeDebutDePartieSontVisiblesEtTemporaires(): void
    {
        $stickman = (new Stickman())
            ->setPv(100)
            ->setAttaque(100)
            ->setDefense(100)
            ->setPassifs([
                [
                    'nom' => 'Fragilité de l’aube',
                    'description' => '-32 % DEF pendant trois rounds.',
                    'type' => 'fragilite_aube',
                    'valeur' => 32,
                ],
                [
                    'nom' => 'Instabilité',
                    'description' => '-30 % ATQ pendant trois rounds.',
                    'type' => 'instabilite',
                    'valeur' => 30,
                ],
            ]);
        $service = new PassifCombatService();

        self::assertSame(-30, $service->bonusAttaquePourcentage([$stickman], 1));
        self::assertSame(-32, $service->bonusDefensePourcentage([$stickman], 3));
        self::assertSame(0, $service->bonusAttaquePourcentage([$stickman], 4));
        self::assertSame(0, $service->bonusDefensePourcentage([$stickman], 4));
    }

    public function testLePassifDernierSurvivantEstFortMaisDifficileAActiver(): void
    {
        $stickman = (new Stickman())
            ->setPv(100)
            ->setAttaque(100)
            ->setDefense(0)
            ->setPassifs([[
                'nom' => 'Dernier survivant',
                'description' => '+50 % ATQ à partir du round 8 seul.',
                'type' => 'dernier_survivant',
                'valeur' => 50,
                'a_partir_round' => 8,
            ]]);
        $contexte = [
            'attaquants' => [[
                'stickman' => $stickman,
                'pvActuels' => 100,
                'pvMaximum' => 100,
                'partenaireVivant' => false,
                'protegeAllie' => false,
            ]],
        ];
        $service = new PassifCombatService();

        self::assertSame(0, $service->bonusAttaquePourcentage([$stickman], 7, $contexte));
        self::assertSame(50, $service->bonusAttaquePourcentage([$stickman], 8, $contexte));
    }
}
