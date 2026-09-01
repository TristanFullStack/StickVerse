<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\Stickman;
use App\Entity\User;
use App\Service\ClassementEloService;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class ClassementEloServiceTest extends TestCase
{
    public function testUnMatchNulNeChangePasLesCotesIdentiques(): void
    {
        [$combat, $joueur1, $joueur2] = $this->creerCombat();
        $combat->setStatut(Combat::STATUT_TERMINE);

        $variations = (new ClassementEloService())
            ->mettreAJourSiNecessaire($combat);

        self::assertSame(['joueur1' => 0, 'joueur2' => 0], $variations);
        self::assertSame(User::ELO_DEPART, $joueur1->getElo());
        self::assertSame(User::ELO_DEPART, $joueur2->getElo());
    }

    public function testUneVictoireDUnJoueurMieuxClasseRapporteMoins(): void
    {
        [$combat, $joueur1, $joueur2] = $this->creerCombat();
        $joueur1->setElo(1400);
        $joueur2->setElo(1000);
        $combat
            ->setGagnant($joueur1)
            ->setStatut(Combat::STATUT_TERMINE);

        $variations = (new ClassementEloService())
            ->mettreAJourSiNecessaire($combat);

        self::assertSame(3, $variations['joueur1']);
        self::assertSame(-3, $variations['joueur2']);
    }

    public function testUnForfaitEstTraiteCommeUneVictoire(): void
    {
        [$combat, $joueur1, $joueur2] = $this->creerCombat();
        $combat
            ->setGagnant($joueur2)
            ->setStatut(Combat::STATUT_FORFAIT);

        (new ClassementEloService())->mettreAJourSiNecessaire($combat);

        self::assertSame(984, $joueur1->getElo());
        self::assertSame(1016, $joueur2->getElo());
    }

    public function testUnCombatPriveNeModifieJamaisLeClassement(): void
    {
        [$combat, $joueur1, $joueur2] = $this->creerCombat();
        $combat
            ->setPrive(true)
            ->setGagnant($joueur1)
            ->setStatut(Combat::STATUT_TERMINE);

        $variations = (new ClassementEloService())
            ->mettreAJourSiNecessaire($combat);

        self::assertSame(['joueur1' => 0, 'joueur2' => 0], $variations);
        self::assertSame(User::ELO_DEPART, $joueur1->getElo());
        self::assertSame(User::ELO_DEPART, $joueur2->getElo());
        self::assertTrue($combat->estEloAttribuee());
    }

    public function testUneVictoireAvecUneEquipePlusFaibleRapporteUnBonus(): void
    {
        [$combat, $joueur1, $joueur2] = $this->creerCombat();
        $this->ajouterEquipeAuCombat($combat, $joueur1, 60, 12, 14, 1);
        $this->ajouterEquipeAuCombat($combat, $joueur2, 520, 120, 120, 10);
        $combat
            ->setGagnant($joueur1)
            ->setStatut(Combat::STATUT_TERMINE);

        $variations = (new ClassementEloService())
            ->mettreAJourSiNecessaire($combat);

        self::assertGreaterThan(16, $variations['joueur1']);
        self::assertSame(-$variations['joueur1'], $variations['joueur2']);
    }

    public function testUneVictoireAvecUneEquipePlusForteSubitUnMalus(): void
    {
        [$combat, $joueur1, $joueur2] = $this->creerCombat();
        $this->ajouterEquipeAuCombat($combat, $joueur1, 520, 120, 120, 20);
        $this->ajouterEquipeAuCombat($combat, $joueur2, 60, 12, 14, 30);
        $combat
            ->setGagnant($joueur1)
            ->setStatut(Combat::STATUT_TERMINE);

        $variations = (new ClassementEloService())
            ->mettreAJourSiNecessaire($combat);

        self::assertLessThan(16, $variations['joueur1']);
        self::assertGreaterThanOrEqual(0, $variations['joueur1']);
        self::assertSame(-$variations['joueur1'], $variations['joueur2']);
    }

    public function testLesEcartsDEloEtDePuissanceSeCumulentPourLOutsider(): void
    {
        [$combat, $joueur1, $joueur2] = $this->creerCombat();
        $joueur1->setElo(900);
        $joueur2->setElo(1100);
        $this->ajouterEquipeAuCombat($combat, $joueur1, 60, 12, 14, 40);
        $this->ajouterEquipeAuCombat($combat, $joueur2, 520, 120, 120, 50);
        $combat
            ->setGagnant($joueur1)
            ->setStatut(Combat::STATUT_TERMINE);

        $variations = (new ClassementEloService())
            ->mettreAJourSiNecessaire($combat);

        self::assertSame(30, $variations['joueur1']);
        self::assertSame(-30, $variations['joueur2']);
        self::assertSame(930, $joueur1->getElo());
        self::assertSame(1070, $joueur2->getElo());
    }

    /**
     * @return array{Combat, User, User}
     */
    private function creerCombat(): array
    {
        $joueur1 = new User();
        $joueur2 = new User();
        $combat = (new Combat($joueur1))
            ->setJoueur2($joueur2)
            ->setStatut(Combat::STATUT_EN_COURS);

        return [$combat, $joueur1, $joueur2];
    }

    private function ajouterEquipeAuCombat(
        Combat $combat,
        User $joueur,
        int $pv,
        int $attaque,
        int $defense,
        int $premierId,
    ): void {
        foreach (['A', 'B', 'C', 'D'] as $index => $slot) {
            $stickman = (new Stickman())
                ->setNom('Stickman '.$slot)
                ->setSlug('stickman-'.$premierId.'-'.$slot)
                ->setDescription('Stickman de test ELO.')
                ->setImage('stickman-'.$slot.'.png')
                ->setRarete(1)
                ->setPv($pv)
                ->setAttaque($attaque)
                ->setDefense($defense)
                ->setStatutActif(true);
            (new ReflectionProperty(Stickman::class, 'id'))
                ->setValue($stickman, $premierId + $index);

            new CombattantCombat($combat, $joueur, $slot, $stickman);
        }
    }
}
