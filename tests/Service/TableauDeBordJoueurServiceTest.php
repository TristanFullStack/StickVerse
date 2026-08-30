<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\Equipe;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Repository\EquipeRepository;
use App\Repository\InventaireRepository;
use App\Service\TableauDeBordJoueurService;
use PHPUnit\Framework\TestCase;

final class TableauDeBordJoueurServiceTest extends TestCase
{
    public function testConstruitLeResumeDuJoueur(): void
    {
        $joueur = (new User())->setEmail('joueur@example.com');
        $equipe = (new Equipe())->setNom('Équipe principale');
        $combatActif = new Combat($joueur);
        $ancienCombat = (new Combat($joueur))
            ->setJoueur2(new User())
            ->setStatut(Combat::STATUT_TERMINE);

        $inventaireRepository = $this->createMock(
            InventaireRepository::class
        );
        $equipeRepository = $this->createMock(
            EquipeRepository::class
        );
        $combatRepository = $this->createMock(
            CombatRepository::class
        );

        $inventaireRepository
            ->expects(self::once())
            ->method('count')
            ->with(['utilisateur' => $joueur])
            ->willReturn(12);

        $equipeRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['utilisateur' => $joueur])
            ->willReturn($equipe);

        $combatRepository
            ->expects(self::once())
            ->method('trouverActifPourJoueur')
            ->with($joueur)
            ->willReturn($combatActif);

        $combatRepository
            ->expects(self::once())
            ->method('trouverHistoriquePourJoueur')
            ->with($joueur, 3)
            ->willReturn([$ancienCombat]);

        $resultat = (new TableauDeBordJoueurService(
            $inventaireRepository,
            $equipeRepository,
            $combatRepository,
        ))->construire($joueur);

        self::assertSame(12, $resultat['nombreStickmen']);
        self::assertSame($equipe, $resultat['equipe']);
        self::assertSame($combatActif, $resultat['combatActif']);
        self::assertSame([$ancienCombat], $resultat['derniersCombats']);
    }
}
