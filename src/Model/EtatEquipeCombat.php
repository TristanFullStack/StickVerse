<?php

namespace App\Model;

use App\Entity\Equipe;
use App\Entity\Stickman;
use InvalidArgumentException;
use LogicException;

final class EtatEquipeCombat
{
    private const SLOTS_PAR_GROUPE = [
        'X' => ['A', 'B'],
        'Y' => ['C', 'D'],
    ];

    /**
     * @var array<string, Stickman>
     */
    private array $stickmen;

    /**
     * @var array<string, int>
     */
    private array $pvActuels = [];

    public function __construct(Equipe $equipe)
    {
        $this->stickmen = [
            'A' => $equipe->getStickmanA(),
            'B' => $equipe->getStickmanB(),
            'C' => $equipe->getStickmanC(),
            'D' => $equipe->getStickmanD(),
        ];

        foreach ($this->stickmen as $slot => $stickman) {
            if (!$stickman instanceof Stickman) {
                throw new LogicException(
                    sprintf('Le slot %s ne contient aucun Stickman.', $slot)
                );
            }

            $pvMaximum = $stickman->getPv();

            if ($pvMaximum === null) {
                throw new LogicException(
                    sprintf('Le Stickman du slot %s ne possède pas de PV.', $slot)
                );
            }

            $this->pvActuels[$slot] = $pvMaximum;
        }
    }

    public function getStickman(string $slot): Stickman
    {
        $this->verifierSlot($slot);

        return $this->stickmen[$slot];
    }

    public function getPvActuels(string $slot): int
    {
        $this->verifierSlot($slot);

        return $this->pvActuels[$slot];
    }

    public function estVivant(string $slot): bool
    {
        return $this->getPvActuels($slot) > 0;
    }

    /**
     * @return list<Stickman>
     */
    public function getStickmenVivantsDuGroupe(string $groupe): array
    {
        if (!isset(self::SLOTS_PAR_GROUPE[$groupe])) {
            throw new InvalidArgumentException(
                'Le groupe doit être X ou Y.'
            );
        }

        $stickmenVivants = [];

        foreach (self::SLOTS_PAR_GROUPE[$groupe] as $slot) {
            if ($this->estVivant($slot)) {
                $stickmenVivants[] = $this->getStickman($slot);
            }
        }

        return $stickmenVivants;
    }

    public function appliquerPvRestants(string $slot, int $pvRestants): void
    {
        $this->verifierSlot($slot);

        $pvMaximum = $this->getStickman($slot)->getPv() ?? 0;

        if ($pvRestants < 0 || $pvRestants > $pvMaximum) {
            throw new InvalidArgumentException(
                'Les PV restants doivent être compris entre 0 et les PV maximum.'
            );
        }

        $this->pvActuels[$slot] = $pvRestants;
    }

    /**
     * @return array<string, int>
     */
    public function getTousLesPv(): array
    {
        return $this->pvActuels;
    }

    private function verifierSlot(string $slot): void
    {
        if (!isset($this->stickmen[$slot])) {
            throw new InvalidArgumentException(
                'Le slot doit être A, B, C ou D.'
            );
        }
    }
}