<?php

namespace App\Tests\Service;

use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Model\EtatEquipeCombat;
use App\Model\PlanCombat;
use App\Service\CombatService;
use App\Service\ResolutionRoundService;
use PHPUnit\Framework\TestCase;

final class ResolutionRoundServiceTest extends TestCase
{
    public function testLeRoundQuatreAppliqueLePremierPalierDePression(): void
    {
        $joueur1 = new EtatEquipeCombat($this->creerEquipeJoueur1());
        $joueur2 = new EtatEquipeCombat($this->creerEquipeJoueur2());
        $plan = new PlanCombat(
            cibleAttaqueX: 'A',
            cibleAttaqueY: 'A',
            cibleDefenseX: 'B',
            cibleDefenseY: 'C',
        );
        $service = new ResolutionRoundService(new CombatService());

        $resultats = $service->resoudre(
            joueur1: $joueur1,
            planJoueur1: $plan,
            joueur2: $joueur2,
            planJoueur2: $plan,
            numeroRound: 4,
        );

        self::assertSame(11, $resultats['joueur1_A']['attaque']);
        self::assertSame(11, $resultats['joueur2_A']['attaque']);
    }

    public function testResoudreUnRoundCompletEtSimultane(): void
    {
        $joueur1 = new EtatEquipeCombat(
            $this->creerEquipeJoueur1()
        );

        $joueur2 = new EtatEquipeCombat(
            $this->creerEquipeJoueur2()
        );

        /*
         * Joueur 1 :
         * - X et Y attaquent A : focus ;
         * - X défend A ;
         * - Y défend B.
         */
        $planJoueur1 = new PlanCombat(
            cibleAttaqueX: 'A',
            cibleAttaqueY: 'A',
            cibleDefenseX: 'A',
            cibleDefenseY: 'B',
        );

        /*
         * Joueur 2 :
         * - X attaque A ;
         * - Y attaque B : split ;
         * - X et Y défendent A : double défense.
         */
        $planJoueur2 = new PlanCombat(
            cibleAttaqueX: 'A',
            cibleAttaqueY: 'B',
            cibleDefenseX: 'A',
            cibleDefenseY: 'A',
        );

        $service = new ResolutionRoundService(
            new CombatService()
        );

        $resultats = $service->resoudre(
            joueur1: $joueur1,
            planJoueur1: $planJoueur1,
            joueur2: $joueur2,
            planJoueur2: $planJoueur2,
        );

        /*
         * Focus du joueur 1 sur le slot A du joueur 2 :
         *
         * ATK : équipe X 6 + équipe Y 4 = 10
         * DEF : équipe X 3 + équipe Y 3 = 6
         * Dégâts : 10 - 6 = 4
         * PV : 6 - 4 = 2
         */
        self::assertSame(
            10,
            $resultats['joueur2_A']['attaque'],
        );

        self::assertSame(
            6,
            $resultats['joueur2_A']['defense'],
        );

        self::assertSame(
            4,
            $resultats['joueur2_A']['degatsCalcules'],
        );

        self::assertSame(
            2,
            $joueur2->getPvActuels('A'),
        );

        /*
         * Attaque X du joueur 2 sur le slot A du joueur 1 :
         *
         * ATK : 3 + 1 = 4
         * DEF : 2 + 1 = 3
         * Dégâts : 1
         * PV : 5 - 1 = 4
         */
        self::assertSame(
            4,
            $resultats['joueur1_A']['attaque'],
        );

        self::assertSame(
            3,
            $resultats['joueur1_A']['defense'],
        );

        self::assertSame(
            1,
            $resultats['joueur1_A']['degatsCalcules'],
        );

        self::assertSame(
            4,
            $joueur1->getPvActuels('A'),
        );

        /*
         * Attaque Y du joueur 2 sur le slot B du joueur 1 :
         *
         * ATK : 4 + 2 = 6
         * DEF : 3 + 4 = 7
         * Dégâts : 0
         * PV inchangés : 4
         */
        self::assertSame(
            6,
            $resultats['joueur1_B']['attaque'],
        );

        self::assertSame(
            7,
            $resultats['joueur1_B']['defense'],
        );

        self::assertSame(
            0,
            $resultats['joueur1_B']['degatsCalcules'],
        );

        self::assertSame(
            4,
            $joueur1->getPvActuels('B'),
        );
    }

    private function creerEquipeJoueur1(): Equipe
    {
        $equipe = new Equipe();

        $equipe->setStickmanA(
            $this->creerStickman(pv: 5, attaque: 2, defense: 2)
        );

        $equipe->setStickmanB(
            $this->creerStickman(pv: 4, attaque: 4, defense: 1)
        );

        $equipe->setStickmanC(
            $this->creerStickman(pv: 4, attaque: 2, defense: 3)
        );

        $equipe->setStickmanD(
            $this->creerStickman(pv: 8, attaque: 2, defense: 4)
        );

        return $equipe;
    }

    private function creerEquipeJoueur2(): Equipe
    {
        $equipe = new Equipe();

        $equipe->setStickmanA(
            $this->creerStickman(pv: 6, attaque: 3, defense: 1)
        );

        $equipe->setStickmanB(
            $this->creerStickman(pv: 5, attaque: 1, defense: 2)
        );

        $equipe->setStickmanC(
            $this->creerStickman(pv: 4, attaque: 4, defense: 1)
        );

        $equipe->setStickmanD(
            $this->creerStickman(pv: 6, attaque: 2, defense: 2)
        );

        return $equipe;
    }

    private function creerStickman(
        int $pv,
        int $attaque,
        int $defense,
    ): Stickman {
        $stickman = new Stickman();

        $stickman->setPv($pv);
        $stickman->setAttaque($attaque);
        $stickman->setDefense($defense);

        return $stickman;
    }
}
