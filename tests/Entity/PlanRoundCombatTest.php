<?php

namespace App\Tests\Entity;

use App\Entity\Combat;
use App\Entity\PlanRoundCombat;
use App\Entity\User;
use App\Model\PlanCombat;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PlanRoundCombatTest extends TestCase
{
    public function testCopieLePlanEtLAssocieAuCombat(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);

        $planCombat = new PlanCombat(
            'A',
            'B',
            'C',
            'D',
        );

        $planRound = new PlanRoundCombat(
            $combat,
            $joueur2,
            $planCombat,
        );

        self::assertNull($planRound->getId());
        self::assertSame($combat, $planRound->getCombat());
        self::assertSame($joueur2, $planRound->getJoueur());
        self::assertSame(1, $planRound->getNumeroRound());

        self::assertSame('A', $planRound->getCibleAttaqueX());
        self::assertSame('B', $planRound->getCibleAttaqueY());
        self::assertSame('C', $planRound->getCibleDefenseX());
        self::assertSame('D', $planRound->getCibleDefenseY());

        self::assertInstanceOf(
            DateTimeImmutable::class,
            $planRound->getDateSoumission(),
        );

        self::assertTrue(
            $combat->getPlans()->contains($planRound),
        );
    }

    public function testReconstruitLeModelePlanCombat(): void
    {
        $joueur = new User();
        $combat = new Combat($joueur);

        $planOriginal = new PlanCombat(
            'A',
            'A',
            'C',
            'C',
        );

        $planRound = new PlanRoundCombat(
            $combat,
            $joueur,
            $planOriginal,
        );

        $planReconstruit = $planRound->toPlanCombat();

        self::assertNotSame(
            $planOriginal,
            $planReconstruit,
        );

        self::assertSame(
            'A',
            $planReconstruit->getCibleAttaqueX(),
        );

        self::assertSame(
            'A',
            $planReconstruit->getCibleAttaqueY(),
        );

        self::assertSame(
            'C',
            $planReconstruit->getCibleDefenseX(),
        );

        self::assertSame(
            'C',
            $planReconstruit->getCibleDefenseY(),
        );

        self::assertTrue($planReconstruit->estFocus());
        self::assertTrue($planReconstruit->estDoubleDefense());
    }

    public function testConserveLeNumeroDuRoundSoumis(): void
    {
        $joueur = new User();
        $combat = new Combat($joueur);

        $combat->setNumeroRound(3);

        $planRound = new PlanRoundCombat(
            $combat,
            $joueur,
            new PlanCombat('A', 'B', 'C', 'D'),
        );

        $combat->passerAuRoundSuivant();

        self::assertSame(3, $planRound->getNumeroRound());
        self::assertSame(4, $combat->getNumeroRound());
    }

    public function testRefuseUnJoueurExterieurAuCombat(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();
        $intrus = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);

        $this->expectException(
            InvalidArgumentException::class,
        );

        $this->expectExceptionMessage(
            'Le joueur doit participer au combat pour soumettre un plan.',
        );

        new PlanRoundCombat(
            $combat,
            $intrus,
            new PlanCombat('A', 'B', 'C', 'D'),
        );
    }

    public function testNePossedeAucunSetterDeModification(): void
    {
        $settersInterdits = [
            'setCombat',
            'setJoueur',
            'setNumeroRound',
            'setCibleAttaqueX',
            'setCibleAttaqueY',
            'setCibleDefenseX',
            'setCibleDefenseY',
            'setDateSoumission',
        ];

        foreach ($settersInterdits as $setter) {
            self::assertFalse(
                method_exists(PlanRoundCombat::class, $setter),
                sprintf('La méthode %s ne doit pas exister.', $setter),
            );
        }
    }
}