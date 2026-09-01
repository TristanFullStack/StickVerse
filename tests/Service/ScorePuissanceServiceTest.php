<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Entity\User;
use App\Service\ScorePuissanceService;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ScorePuissanceServiceTest extends TestCase
{
    public function testCalculeLaPuissanceDUnStickmanDepuisSesStatistiques(): void
    {
        $stickman = $this->creerStickman(1, 340, 55, 70);

        self::assertSame(
            283,
            (new ScorePuissanceService())->calculerStickman($stickman),
        );
    }

    public function testAdditionneLesQuatrePuissancesDUneEquipe(): void
    {
        $equipe = (new Equipe())
            ->setStickmanA($this->creerStickman(1, 340, 55, 70))
            ->setStickmanB($this->creerStickman(2, 260, 80, 45))
            ->setStickmanC($this->creerStickman(3, 300, 65, 60))
            ->setStickmanD($this->creerStickman(4, 520, 35, 120));

        self::assertSame(
            1197,
            (new ScorePuissanceService())->calculerEquipe($equipe),
        );
    }

    public function testLeCombatConserveLaPuissanceDesSnapshots(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();
        $combat = (new Combat($joueur1))->setJoueur2($joueur2);
        $stickman = $this->creerStickman(1, 340, 55, 70);

        new CombattantCombat($combat, $joueur1, 'A', $stickman);
        $stickman->setPv(1)->setAttaque(1)->setDefense(1);

        self::assertSame(
            283,
            (new ScorePuissanceService())
                ->calculerCombatPourJoueur($combat, $joueur1),
        );
        self::assertSame(
            0,
            (new ScorePuissanceService())
                ->calculerCombatPourJoueur($combat, $joueur2),
        );
    }

    private function creerStickman(
        int $id,
        int $pv,
        int $attaque,
        int $defense,
    ): Stickman {
        $stickman = (new Stickman())
            ->setNom('Stickman '.$id)
            ->setSlug('stickman-'.$id)
            ->setDescription('Stickman de test.')
            ->setImage('stickman-'.$id.'.png')
            ->setRarete(1)
            ->setPv($pv)
            ->setAttaque($attaque)
            ->setDefense($defense)
            ->setStatutActif(true);

        (new ReflectionProperty(Stickman::class, 'id'))
            ->setValue($stickman, $id);

        return $stickman;
    }
}
