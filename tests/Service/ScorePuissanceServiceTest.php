<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Entity\User;
use App\Service\PassifCombatService;
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

    public function testLesPassifsAugmententLaPuissanceDeLaCarte(): void
    {
        $stickman = $this->creerStickman(1, 100, 50, 40)
            ->setPassifs([
                [
                    'nom' => 'Frappe précise',
                    'description' => '+10 % ATQ.',
                    'type' => PassifCombatService::TYPE_BONUS_ATTAQUE_POURCENTAGE,
                    'valeur' => 10,
                ],
                [
                    'nom' => 'Garde légère',
                    'description' => '+10 % DEF.',
                    'type' => PassifCombatService::TYPE_BONUS_DEFENSE_POURCENTAGE,
                    'valeur' => 10,
                ],
            ]);

        $service = new ScorePuissanceService(new PassifCombatService());

        // Base : 20 PV + 100 ATQ + 60 DEF = 180.
        // Passifs : 10 % de 100 + 10 % de 60 = 16 points.
        self::assertSame(196, $service->calculerStickman($stickman));
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

    public function testCalculeLaLimiteDEquipeSelonLElo(): void
    {
        $service = new ScorePuissanceService();

        self::assertSame(500, $service->limiteEquipePourElo(0));
        self::assertSame(500, $service->limiteEquipePourElo(999));
        self::assertSame(1000, $service->limiteEquipePourElo(1000));
        self::assertSame(1000, $service->limiteEquipePourElo(1499));
        self::assertSame(1500, $service->limiteEquipePourElo(1500));
    }

    public function testExposeLesFourchettesOfficiellesParRarete(): void
    {
        $service = new ScorePuissanceService();

        self::assertSame(['min' => 70, 'max' => 130], $service->fourchettePourRarete(1));
        self::assertSame(['min' => 130, 'max' => 220], $service->fourchettePourRarete(2));
        self::assertSame(['min' => 220, 'max' => 300], $service->fourchettePourRarete(3));
        self::assertSame(['min' => 300, 'max' => 500], $service->fourchettePourRarete(4));
        self::assertSame(['min' => 500, 'max' => null], $service->fourchettePourRarete(5));
    }

    public function testVerifieLaCompatibiliteEntrePuissanceEtRarete(): void
    {
        $service = new ScorePuissanceService(new PassifCombatService());
        $carte = $this->creerStickman(1, 60, 12, 14)
            ->setPassifs([[
                'type' => PassifCombatService::TYPE_BONUS_ATTAQUE_POURCENTAGE,
                'valeur' => 10,
                'puissance' => 32,
            ]])
            ->setRarete(1);

        self::assertTrue($service->puissanceCompatibleRarete($carte));

        $carte->setRarete(2);
        self::assertFalse($service->puissanceCompatibleRarete($carte));
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
