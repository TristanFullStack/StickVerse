<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\User;
use App\Service\ClassementEloService;
use PHPUnit\Framework\TestCase;

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
}
