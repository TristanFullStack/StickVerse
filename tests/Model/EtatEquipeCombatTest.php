<?php

namespace App\Tests\Model;

use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Model\EtatEquipeCombat;
use PHPUnit\Framework\TestCase;

final class EtatEquipeCombatTest extends TestCase
{
    public function testInitialiserLesPvDesQuatreStickmans(): void
    {
        $etatEquipe = new EtatEquipeCombat(
            $this->creerEquipe()
        );

        self::assertSame([
            'A' => 5,
            'B' => 4,
            'C' => 4,
            'D' => 8,
        ], $etatEquipe->getTousLesPv());

        self::assertCount(
            2,
            $etatEquipe->getStickmenVivantsDuGroupe('X'),
        );

        self::assertCount(
            2,
            $etatEquipe->getStickmenVivantsDuGroupe('Y'),
        );
    }

    public function testUnKoNeModifiePasLesPvPermanentsDuStickman(): void
    {
        $etatEquipe = new EtatEquipeCombat(
            $this->creerEquipe()
        );

        $etatEquipe->appliquerPvRestants('A', 0);

        self::assertFalse($etatEquipe->estVivant('A'));
        self::assertSame(0, $etatEquipe->getPvActuels('A'));

        self::assertCount(
            1,
            $etatEquipe->getStickmenVivantsDuGroupe('X'),
        );

        self::assertSame(
            5,
            $etatEquipe->getStickman('A')->getPv(),
        );
    }

    private function creerEquipe(): Equipe
    {
        $equipe = new Equipe();

        $equipe->setStickmanA($this->creerStickman(5));
        $equipe->setStickmanB($this->creerStickman(4));
        $equipe->setStickmanC($this->creerStickman(4));
        $equipe->setStickmanD($this->creerStickman(8));

        return $equipe;
    }

    private function creerStickman(int $pv): Stickman
    {
        $stickman = new Stickman();
        $stickman->setPv($pv);

        return $stickman;
    }
}