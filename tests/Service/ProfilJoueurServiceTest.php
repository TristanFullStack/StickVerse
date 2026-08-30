<?php

namespace App\Tests\Service;

use App\Entity\Equipe;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Repository\EquipeRepository;
use App\Repository\InventaireRepository;
use App\Service\ProfilJoueurService;
use PHPUnit\Framework\TestCase;

final class ProfilJoueurServiceTest extends TestCase
{
    public function testConstruitLeProfilDuJoueur(): void
    {
        $joueur = (new User())->setEmail('profil@example.com');
        $equipe = (new Equipe())->setNom('Équipe profil');
        $statistiques = [
            'total' => 8,
            'victoires' => 5,
            'defaites' => 2,
            'matchsNuls' => 1,
        ];

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
            ->willReturn(27);

        $equipeRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['utilisateur' => $joueur])
            ->willReturn($equipe);

        $combatRepository
            ->expects(self::once())
            ->method('calculerStatistiquesPourJoueur')
            ->with($joueur)
            ->willReturn($statistiques);

        $profil = (new ProfilJoueurService(
            $inventaireRepository,
            $equipeRepository,
            $combatRepository,
        ))->construire($joueur);

        self::assertSame(27, $profil['nombreStickmen']);
        self::assertSame($equipe, $profil['equipe']);
        self::assertSame($statistiques, $profil['statistiques']);
    }
}
