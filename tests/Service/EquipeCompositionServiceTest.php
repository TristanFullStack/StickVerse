<?php

namespace App\Tests\Service;

use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Service\EquipeCompositionService;
use App\Service\ScorePuissanceService;
use PHPUnit\Framework\TestCase;

final class EquipeCompositionServiceTest extends TestCase
{
    public function testResumeLesStatistiquesParGroupeEtAuTotal(): void
    {
        $stickmen = [
            $this->creerStickman('A', 100, 20, 10),
            $this->creerStickman('B', 200, 30, 15),
            $this->creerStickman('C', 150, 25, 20),
            $this->creerStickman('D', 80, 10, 5),
        ];
        $equipe = (new Equipe())
            ->setNom('Équipe test')
            ->setStickmanA($stickmen[0])
            ->setStickmanB($stickmen[1])
            ->setStickmanC($stickmen[2])
            ->setStickmanD($stickmen[3]);

        $resume = (new EquipeCompositionService(new ScorePuissanceService()))
            ->resumer($equipe);

        self::assertSame(50, $resume['groupes']['X']['attaque']);
        self::assertSame(25, $resume['groupes']['X']['defense']);
        self::assertSame(300, $resume['groupes']['X']['pv']);
        self::assertSame(35, $resume['groupes']['Y']['attaque']);
        self::assertSame(25, $resume['groupes']['Y']['defense']);
        self::assertSame(230, $resume['groupes']['Y']['pv']);
        self::assertSame(85, $resume['total']['attaque']);
        self::assertSame(50, $resume['total']['defense']);
        self::assertSame(530, $resume['total']['pv']);
        self::assertCount(4, $resume['slots']);
        self::assertSame('A', $resume['slots'][0]['slot']);
        self::assertSame(75, $resume['slots'][0]['puissance']);
    }

    private function creerStickman(
        string $nom,
        int $pv,
        int $attaque,
        int $defense,
    ): Stickman {
        return (new Stickman())
            ->setNom($nom)
            ->setSlug('slug-'.$nom)
            ->setDescription('Stickman de test')
            ->setImage($nom.'.png')
            ->setRarete(1)
            ->setPv($pv)
            ->setAttaque($attaque)
            ->setDefense($defense)
            ->setStatutActif(true);
    }
}
