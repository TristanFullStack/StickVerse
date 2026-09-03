<?php

namespace App\Tests\Service;

use App\Entity\Stickman;
use App\Service\CombatService;
use App\Service\PassifCombatService;
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
}
