<?php

namespace App\Model;

use InvalidArgumentException;

final readonly class PlanCombat
{
    private const SLOTS_VALIDES = [
        'A',
        'B',
        'C',
        'D',
    ];

    public function __construct(
        private string $cibleAttaqueX,
        private string $cibleAttaqueY,
        private string $cibleDefenseX,
        private string $cibleDefenseY,
    ) {
        $cibles = [
            $this->cibleAttaqueX,
            $this->cibleAttaqueY,
            $this->cibleDefenseX,
            $this->cibleDefenseY,
        ];

        foreach ($cibles as $cible) {
            if (!in_array($cible, self::SLOTS_VALIDES, true)) {
                throw new InvalidArgumentException(
                    'Une cible doit correspondre au slot A, B, C ou D.'
                );
            }
        }
    }

    public function getCibleAttaqueX(): string
    {
        return $this->cibleAttaqueX;
    }

    public function getCibleAttaqueY(): string
    {
        return $this->cibleAttaqueY;
    }

    public function getCibleDefenseX(): string
    {
        return $this->cibleDefenseX;
    }

    public function getCibleDefenseY(): string
    {
        return $this->cibleDefenseY;
    }

    public function estFocus(): bool
    {
        return $this->cibleAttaqueX === $this->cibleAttaqueY;
    }

    public function estSplit(): bool
    {
        return $this->cibleAttaqueX !== $this->cibleAttaqueY;
    }

    public function estDoubleDefense(): bool
    {
        return $this->cibleDefenseX === $this->cibleDefenseY;
    }
}