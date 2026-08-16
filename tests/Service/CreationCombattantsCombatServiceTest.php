<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Entity\User;
use App\Service\CreationCombattantsCombatService;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class CreationCombattantsCombatServiceTest extends TestCase
{
    public function testCreeLesQuatreSnapshotsDuJoueur(): void
    {
        [$joueur, $combat, $equipe] = $this->creerContexte();

        $service = new CreationCombattantsCombatService();

        $combattants = $service->creerPourJoueur(
            $combat,
            $joueur,
            $equipe,
        );

        self::assertCount(4, $combattants);
        self::assertCount(4, $combat->getCombattants());

        self::assertSame('A', $combattants[0]->getSlot());
        self::assertSame('B', $combattants[1]->getSlot());
        self::assertSame('C', $combattants[2]->getSlot());
        self::assertSame('D', $combattants[3]->getSlot());

        self::assertSame('Guerrier', $combattants[0]->getNomSnapshot());
        self::assertSame('Card01Guerrier.png', $combattants[0]->getImageSnapshot());
        self::assertSame(1, $combattants[0]->getRareteSnapshot());
        self::assertSame(5, $combattants[0]->getPvMaximum());
        self::assertSame(5, $combattants[0]->getPvActuels());
        self::assertSame(2, $combattants[0]->getAttaqueSnapshot());
        self::assertSame(2, $combattants[0]->getDefenseSnapshot());
        self::assertSame($joueur, $combattants[0]->getJoueur());
        self::assertSame($combat, $combattants[0]->getCombat());
    }

    public function testLesStatistiquesDuSnapshotRestentFigees(): void
    {
        [$joueur, $combat, $equipe] = $this->creerContexte();

        $service = new CreationCombattantsCombatService();

        $combattants = $service->creerPourJoueur(
            $combat,
            $joueur,
            $equipe,
        );

        $snapshotGuerrier = $combattants[0];
        $guerrierOriginal = $equipe->getStickmanA();

        self::assertInstanceOf(Stickman::class, $guerrierOriginal);

        $guerrierOriginal->setNom('Guerrier modifié');
        $guerrierOriginal->setPv(999);
        $guerrierOriginal->setAttaque(999);
        $guerrierOriginal->setDefense(999);

        self::assertSame('Guerrier', $snapshotGuerrier->getNomSnapshot());
        self::assertSame(5, $snapshotGuerrier->getPvMaximum());
        self::assertSame(5, $snapshotGuerrier->getPvActuels());
        self::assertSame(2, $snapshotGuerrier->getAttaqueSnapshot());
        self::assertSame(2, $snapshotGuerrier->getDefenseSnapshot());
    }

    public function testRefuseDeCreerDeuxFoisLesSnapshotsDuMemeJoueur(): void
    {
        [$joueur, $combat, $equipe] = $this->creerContexte();

        $service = new CreationCombattantsCombatService();

        $service->creerPourJoueur(
            $combat,
            $joueur,
            $equipe,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Les snapshots de ce joueur existent déjà pour ce combat.'
        );

        $service->creerPourJoueur(
            $combat,
            $joueur,
            $equipe,
        );
    }

    public function testRefuseUneEquipeAppartenantAUnAutreJoueur(): void
    {
        [$joueur, $combat, $equipe] = $this->creerContexte();

        $autreJoueur = new User();
        $autreJoueur->setEmail('autre@stickverse.test');

        $equipe->setUtilisateur($autreJoueur);

        $service = new CreationCombattantsCombatService();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cette équipe n’appartient pas à ce joueur.'
        );

        $service->creerPourJoueur(
            $combat,
            $joueur,
            $equipe,
        );
    }

    /**
     * @return array{0: User, 1: Combat, 2: Equipe}
     */
    private function creerContexte(): array
    {
        $joueur = new User();
        $joueur->setEmail('joueur1@stickverse.test');

        $combat = new Combat($joueur);

        $equipe = new Equipe();
        $equipe->setNom('Équipe principale');
        $equipe->setUtilisateur($joueur);
        $equipe->setStickmanA(
            $this->creerStickman(
                1,
                'Guerrier',
                'Card01Guerrier.png',
                1,
                5,
                2,
                2,
            )
        );
        $equipe->setStickmanB(
            $this->creerStickman(
                2,
                'Archer',
                'Card02Archer.png',
                1,
                4,
                4,
                1,
            )
        );
        $equipe->setStickmanC(
            $this->creerStickman(
                3,
                'Lancier',
                'Card03Lancier.png',
                1,
                4,
                2,
                3,
            )
        );
        $equipe->setStickmanD(
            $this->creerStickman(
                4,
                'Tank',
                'Card04Tank.png',
                2,
                8,
                2,
                4,
            )
        );

        return [$joueur, $combat, $equipe];
    }

    private function creerStickman(
        int $id,
        string $nom,
        string $image,
        int $rarete,
        int $pv,
        int $attaque,
        int $defense,
    ): Stickman {
        $stickman = new Stickman();

        $this->definirId($stickman, $id);

        $stickman->setNom($nom);
        $stickman->setSlug(strtolower($nom));
        $stickman->setDescription('Stickman utilisé pour les tests.');
        $stickman->setImage($image);
        $stickman->setRarete($rarete);
        $stickman->setPv($pv);
        $stickman->setAttaque($attaque);
        $stickman->setDefense($defense);
        $stickman->setStatutActif(true);

        return $stickman;
    }

    private function definirId(object $entite, int $id): void
    {
        $proprieteId = new ReflectionProperty($entite, 'id');
        $proprieteId->setValue($entite, $id);
    }
}