<?php

namespace App\Tests\Model;

use App\Model\PlanCombat;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PlanCombatTest extends TestCase
{
    public function testReconnaitreUnFocusEtUneDoubleDefense(): void
    {
        $plan = new PlanCombat(
            cibleAttaqueX: 'A',
            cibleAttaqueY: 'A',
            cibleDefenseX: 'C',
            cibleDefenseY: 'C',
        );

        self::assertTrue($plan->estFocus());
        self::assertFalse($plan->estSplit());
        self::assertTrue($plan->estDoubleDefense());

        self::assertSame('A', $plan->getCibleAttaqueX());
        self::assertSame('A', $plan->getCibleAttaqueY());
        self::assertSame('C', $plan->getCibleDefenseX());
        self::assertSame('C', $plan->getCibleDefenseY());
    }

    public function testReconnaitreUnSplitEtUneDefenseSeparee(): void
    {
        $plan = new PlanCombat(
            cibleAttaqueX: 'A',
            cibleAttaqueY: 'D',
            cibleDefenseX: 'B',
            cibleDefenseY: 'C',
        );

        self::assertFalse($plan->estFocus());
        self::assertTrue($plan->estSplit());
        self::assertFalse($plan->estDoubleDefense());
    }

    public function testRefuserUneCibleInconnue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new PlanCombat(
            cibleAttaqueX: 'E',
            cibleAttaqueY: 'A',
            cibleDefenseX: 'B',
            cibleDefenseY: 'C',
        );
    }
}