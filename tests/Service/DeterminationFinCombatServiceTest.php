<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\ResultatRoundCombat;
use App\Entity\User;
use App\Service\DeterminationFinCombatService;
use PHPUnit\Framework\TestCase;

final class DeterminationFinCombatServiceTest extends TestCase
{
    public function testDeclareLeJoueur1Gagnant(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $service = new DeterminationFinCombatService();

        $termine = $service->terminerSiNecessaire(
            combat: $combat,
            combattantsJoueur1: $this->creerEquipeVivante(),
            combattantsJoueur2: $this->creerEquipeKo(),
        );

        self::assertTrue($termine);
        self::assertTrue($combat->estTermine());
        self::assertSame($joueur1, $combat->getGagnant());
    }

    public function testDeclareLeJoueur2Gagnant(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $service = new DeterminationFinCombatService();

        $termine = $service->terminerSiNecessaire(
            combat: $combat,
            combattantsJoueur1: $this->creerEquipeKo(),
            combattantsJoueur2: $this->creerEquipeVivante(),
        );

        self::assertTrue($termine);
        self::assertTrue($combat->estTermine());
        self::assertSame($joueur2, $combat->getGagnant());
    }

    public function testDeclareUnMatchNulParEliminationSimultanee(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $service = new DeterminationFinCombatService();

        $termine = $service->terminerSiNecessaire(
            combat: $combat,
            combattantsJoueur1: $this->creerEquipeKo(),
            combattantsJoueur2: $this->creerEquipeKo(),
        );

        self::assertTrue($termine);
        self::assertTrue($combat->estTermine());
        self::assertNull($combat->getGagnant());
    }

    public function testContinueAvantTroisRoundsSansDegat(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $equipeJoueur1 = $this->creerEquipeAvecUnVivant(
            attaque: 2,
            defense: 5,
        );

        $equipeJoueur2 = $this->creerEquipeAvecUnVivant(
            attaque: 3,
            defense: 4,
        );

        $service = new DeterminationFinCombatService();

        $termine = $service->terminerSiNecessaire(
            combat: $combat,
            combattantsJoueur1: $equipeJoueur1,
            combattantsJoueur2: $equipeJoueur2,
        );

        self::assertFalse($termine);
        self::assertTrue($combat->estEnCours());
        self::assertNull($combat->getGagnant());
    }

    public function testContinueSiUnCombattantPeutEncoreInfligerDesDegats(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $equipeJoueur1 = $this->creerEquipeAvecUnVivant(
            attaque: 5,
            defense: 2,
        );

        $equipeJoueur2 = $this->creerEquipeAvecUnVivant(
            attaque: 1,
            defense: 3,
        );

        $service = new DeterminationFinCombatService();

        $termine = $service->terminerSiNecessaire(
            combat: $combat,
            combattantsJoueur1: $equipeJoueur1,
            combattantsJoueur2: $equipeJoueur2,
        );

        self::assertFalse($termine);
        self::assertTrue($combat->estEnCours());
        self::assertNull($combat->getGagnant());
    }

    public function testDeclareUnMatchNulApresTroisRoundsSansDegat(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $this->ajouterRound($combat, 1, 0);
        $this->ajouterRound($combat, 2, 0);
        $this->ajouterRound($combat, 3, 0);

        $service = new DeterminationFinCombatService();

        $termine = $service->terminerSiNecessaire(
            combat: $combat,
            combattantsJoueur1: $this->creerEquipeVivante(),
            combattantsJoueur2: $this->creerEquipeVivante(),
        );

        self::assertTrue($termine);
        self::assertTrue($combat->estTermine());
        self::assertNull($combat->getGagnant());
    }

    public function testLeDernierRoundDepartageSelonLesPvRestants(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);
        $combat->setNumeroRound(Combat::NOMBRE_MAX_ROUNDS);

        $service = new DeterminationFinCombatService();

        $termine = $service->terminerSiNecessaire(
            combat: $combat,
            combattantsJoueur1: $this->creerEquipeAvecPvRestants(12),
            combattantsJoueur2: $this->creerEquipeAvecPvRestants(4),
        );

        self::assertTrue($termine);
        self::assertTrue($combat->estTermine());
        self::assertSame($joueur1, $combat->getGagnant());
    }

    public function testUnRoundAvecDegatReinitialiseLaSerie(): void
    {
        $joueur1 = new User();
        $joueur2 = new User();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $this->ajouterRound($combat, 1, 0);
        $this->ajouterRound($combat, 2, 5);
        $this->ajouterRound($combat, 3, 0);
        $this->ajouterRound($combat, 4, 0);

        $service = new DeterminationFinCombatService();

        $termine = $service->terminerSiNecessaire(
            combat: $combat,
            combattantsJoueur1: $this->creerEquipeVivante(),
            combattantsJoueur2: $this->creerEquipeVivante(),
        );

        self::assertFalse($termine);
        self::assertTrue($combat->estEnCours());
        self::assertNull($combat->getGagnant());
    }

    private function ajouterRound(
        Combat $combat,
        int $numeroRound,
        int $degatsEffectifs,
    ): void {
        new ResultatRoundCombat(
            $combat,
            $numeroRound,
            [
                'joueur1_A' => [
                    'degatsEffectifs' => $degatsEffectifs,
                ],
                'joueur2_A' => [
                    'degatsEffectifs' => 0,
                ],
            ],
        );
    }

    /**
     * @return list<CombattantCombat>
     */
    private function creerEquipeVivante(): array
    {
        return [
            $this->creerCombattant(true, 2, 2),
            $this->creerCombattant(true, 2, 2),
            $this->creerCombattant(true, 2, 2),
            $this->creerCombattant(true, 2, 2),
        ];
    }

    /**
     * @return list<CombattantCombat>
     */
    private function creerEquipeKo(): array
    {
        return [
            $this->creerCombattant(false),
            $this->creerCombattant(false),
            $this->creerCombattant(false),
            $this->creerCombattant(false),
        ];
    }

    /**
     * @return list<CombattantCombat>
     */
    private function creerEquipeAvecUnVivant(
        int $attaque,
        int $defense,
    ): array {
        return [
            $this->creerCombattant(
                vivant: true,
                attaque: $attaque,
                defense: $defense,
            ),
            $this->creerCombattant(false),
            $this->creerCombattant(false),
            $this->creerCombattant(false),
        ];
    }

    /**
     * @return list<CombattantCombat>
     */
    private function creerEquipeAvecPvRestants(int $pv): array
    {
        return [
            $this->creerCombattant(true, 2, 2, $pv),
            $this->creerCombattant(false),
            $this->creerCombattant(false),
            $this->creerCombattant(false),
        ];
    }

    private function creerCombattant(
        bool $vivant,
        int $attaque = 0,
        int $defense = 0,
        int $pv = 5,
    ): CombattantCombat {
        $combattant = $this->createStub(
            CombattantCombat::class
        );

        $combattant
            ->method('estVivant')
            ->willReturn($vivant);

        $combattant
            ->method('getAttaqueSnapshot')
            ->willReturn($attaque);

        $combattant
            ->method('getDefenseSnapshot')
            ->willReturn($defense);

        $combattant
            ->method('getPvActuels')
            ->willReturn($vivant ? $pv : 0);

        return $combattant;
    }
}
