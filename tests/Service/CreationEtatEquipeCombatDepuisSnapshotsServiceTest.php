<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\User;
use App\Service\CreationEtatEquipeCombatDepuisSnapshotsService;
use LogicException;
use PHPUnit\Framework\TestCase;

final class CreationEtatEquipeCombatDepuisSnapshotsServiceTest extends TestCase
{
    public function testCreeUnEtatDepuisLesSnapshots(): void
    {
        $joueur = new User();
        $combat = new Combat($joueur);

        $combattants = [
            $this->creerSnapshot(
                $combat,
                $joueur,
                'A',
                5,
                3,
                2,
                2,
            ),
            $this->creerSnapshot(
                $combat,
                $joueur,
                'B',
                4,
                4,
                4,
                1,
            ),
            $this->creerSnapshot(
                $combat,
                $joueur,
                'C',
                4,
                2,
                2,
                3,
            ),
            $this->creerSnapshot(
                $combat,
                $joueur,
                'D',
                8,
                6,
                2,
                4,
            ),
        ];

        $service =
            new CreationEtatEquipeCombatDepuisSnapshotsService();

        $etatEquipe = $service->creer($combattants);

        $attendus = [
            'A' => [5, 3, 2, 2],
            'B' => [4, 4, 4, 1],
            'C' => [4, 2, 2, 3],
            'D' => [8, 6, 2, 4],
        ];

        foreach ($attendus as $slot => $attendu) {
            [$pvMaximum, $pvActuels, $attaque, $defense] =
                $attendu;

            self::assertSame(
                $pvMaximum,
                $etatEquipe->getStickman($slot)->getPv(),
            );

            self::assertSame(
                $pvActuels,
                $etatEquipe->getPvActuels($slot),
            );

            self::assertSame(
                $attaque,
                $etatEquipe->getStickman($slot)->getAttaque(),
            );

            self::assertSame(
                $defense,
                $etatEquipe->getStickman($slot)->getDefense(),
            );
        }
    }

    public function testRefuseUnSlotManquant(): void
    {
        $joueur = new User();
        $combat = new Combat($joueur);

        $combattants = [
            $this->creerSnapshot(
                $combat,
                $joueur,
                'A',
                5,
                5,
                2,
                2,
            ),
            $this->creerSnapshot(
                $combat,
                $joueur,
                'B',
                4,
                4,
                4,
                1,
            ),
            $this->creerSnapshot(
                $combat,
                $joueur,
                'C',
                4,
                4,
                2,
                3,
            ),
        ];

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Le combattant du slot D est manquant.'
        );

        $service =
            new CreationEtatEquipeCombatDepuisSnapshotsService();

        $service->creer($combattants);
    }

    public function testRefuseUnSlotEnDouble(): void
    {
        $joueur = new User();
        $combat = new Combat($joueur);

        $combattants = [
            $this->creerSnapshot(
                $combat,
                $joueur,
                'A',
                5,
                5,
                2,
                2,
            ),
            $this->creerSnapshot(
                $combat,
                $joueur,
                'A',
                4,
                4,
                4,
                1,
            ),
        ];

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Le slot A est présent plusieurs fois.'
        );

        $service =
            new CreationEtatEquipeCombatDepuisSnapshotsService();

        $service->creer($combattants);
    }

    public function testRefuseDeMelangerDeuxJoueurs(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);

        $combattants = [
            $this->creerSnapshot(
                $combat,
                $joueur1,
                'A',
                5,
                5,
                2,
                2,
            ),
            $this->creerSnapshot(
                $combat,
                $joueur2,
                'B',
                4,
                4,
                4,
                1,
            ),
        ];

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Les combattants doivent appartenir au même joueur.'
        );

        $service =
            new CreationEtatEquipeCombatDepuisSnapshotsService();

        $service->creer($combattants);
    }

    private function creerSnapshot(
        Combat $combat,
        User $joueur,
        string $slot,
        int $pvMaximum,
        int $pvActuels,
        int $attaque,
        int $defense,
    ): CombattantCombat {
        $combattant = $this->createStub(
            CombattantCombat::class
        );

        $combattant
            ->method('getCombat')
            ->willReturn($combat);

        $combattant
            ->method('getJoueur')
            ->willReturn($joueur);

        $combattant
            ->method('getSlot')
            ->willReturn($slot);

        $combattant
            ->method('getPvMaximum')
            ->willReturn($pvMaximum);

        $combattant
            ->method('getPvActuels')
            ->willReturn($pvActuels);

        $combattant
            ->method('getAttaqueSnapshot')
            ->willReturn($attaque);

        $combattant
            ->method('getDefenseSnapshot')
            ->willReturn($defense);

        return $combattant;
    }
}