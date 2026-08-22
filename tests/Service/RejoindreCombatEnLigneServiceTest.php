<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Service\CreationCombattantsCombatService;
use App\Service\RejoindreCombatEnLigneService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class RejoindreCombatEnLigneServiceTest extends TestCase
{
    public function testAjouteLeJoueur2EtDemarreLeCombat(): void
    {
        $joueur1 = $this->creerJoueurEnregistre(
            1,
            'joueur1@stickverse.test',
        );

        $joueur2 = $this->creerJoueurEnregistre(
            2,
            'joueur2@stickverse.test',
        );

        $equipeJoueur1 = $this->creerEquipeEnregistree(
            11,
            $joueur1,
            100,
        );

        $equipeJoueur2 = $this->creerEquipeEnregistree(
            12,
            $joueur2,
            200,
        );

        $combat = new Combat($joueur1);

        $creationCombattantsService =
            new CreationCombattantsCombatService();

        $creationCombattantsService->creerPourJoueur(
            $combat,
            $joueur1,
            $equipeJoueur1,
        );

        self::assertCount(
            4,
            $combat->getCombattants(),
        );

        $entityManager =
            $this->creerEntityManagerTransactionnel();

        $combatRepository = $this->createStub(
            CombatRepository::class
        );

        $combatRepository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn($combat);

        $combatRepository
            ->method('trouverActifPourJoueur')
            ->willReturn(null);

        $service = new RejoindreCombatEnLigneService(
            $entityManager,
            $combatRepository,
            $creationCombattantsService,
        );

        $combatRejoint = $service->rejoindre(
            42,
            $joueur2,
            $equipeJoueur2,
        );

        self::assertSame(
            $combat,
            $combatRejoint,
        );

        self::assertSame(
            $joueur1,
            $combatRejoint->getJoueur1(),
        );

        self::assertSame(
            $joueur2,
            $combatRejoint->getJoueur2(),
        );

        self::assertSame(
            Combat::STATUT_EN_COURS,
            $combatRejoint->getStatut(),
        );

        self::assertSame(
            1,
            $combatRejoint->getNumeroRound(),
        );

        self::assertCount(
            8,
            $combatRejoint->getCombattants(),
        );

        $combattantsJoueur2 = array_values(
            array_filter(
                $combatRejoint
                    ->getCombattants()
                    ->toArray(),
                static fn (
                    CombattantCombat $combattant,
                ): bool => $combattant->getJoueur()
                    === $joueur2,
            )
        );

        self::assertCount(
            4,
            $combattantsJoueur2,
        );

        self::assertSame(
            ['A', 'B', 'C', 'D'],
            array_map(
                static fn (
                    CombattantCombat $combattant,
                ): string => $combattant->getSlot(),
                $combattantsJoueur2,
            ),
        );
    }

    public function testRefuseDeRejoindreSonPropreCombat(): void
    {
        $joueur = $this->creerJoueurEnregistre(
            1,
            'joueur1@stickverse.test',
        );

        $equipe = $this->creerEquipeEnregistree(
            11,
            $joueur,
            100,
        );

        $combat = new Combat($joueur);

        $entityManager =
            $this->creerEntityManagerTransactionnel();

        $combatRepository = $this->createMock(
            CombatRepository::class
        );

        $combatRepository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn($combat);

        $combatRepository
            ->expects(self::never())
            ->method('trouverActifPourJoueur');

        $service = new RejoindreCombatEnLigneService(
            $entityManager,
            $combatRepository,
            new CreationCombattantsCombatService(),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Un joueur ne peut pas rejoindre son propre combat.'
        );

        $service->rejoindre(
            42,
            $joueur,
            $equipe,
        );
    }

    public function testRefuseUnCombatDejaCommence(): void
    {
        $joueur1 = $this->creerJoueurEnregistre(
            1,
            'joueur1@stickverse.test',
        );

        $joueur2 = $this->creerJoueurEnregistre(
            2,
            'joueur2@stickverse.test',
        );

        $joueur3 = $this->creerJoueurEnregistre(
            3,
            'joueur3@stickverse.test',
        );

        $equipeJoueur3 = $this->creerEquipeEnregistree(
            13,
            $joueur3,
            300,
        );

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $entityManager =
            $this->creerEntityManagerTransactionnel();

        $combatRepository = $this->createMock(
            CombatRepository::class
        );

        $combatRepository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn($combat);

        $combatRepository
            ->expects(self::never())
            ->method('trouverActifPourJoueur');

        $service = new RejoindreCombatEnLigneService(
            $entityManager,
            $combatRepository,
            new CreationCombattantsCombatService(),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Ce combat n’est plus disponible.'
        );

        $service->rejoindre(
            42,
            $joueur3,
            $equipeJoueur3,
        );
    }

    public function testRefuseUnJoueurDejaDansUnCombatActif(): void
    {
        $joueur1 = $this->creerJoueurEnregistre(
            1,
            'joueur1@stickverse.test',
        );

        $joueur2 = $this->creerJoueurEnregistre(
            2,
            'joueur2@stickverse.test',
        );

        $equipeJoueur2 = $this->creerEquipeEnregistree(
            12,
            $joueur2,
            200,
        );

        $combatARejoindre = new Combat($joueur1);
        $combatActif = new Combat($joueur2);

        $entityManager =
            $this->creerEntityManagerTransactionnel();

        $combatRepository = $this->createStub(
            CombatRepository::class
        );

        $combatRepository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn($combatARejoindre);

        $combatRepository
            ->method('trouverActifPourJoueur')
            ->willReturn($combatActif);

        $service = new RejoindreCombatEnLigneService(
            $entityManager,
            $combatRepository,
            new CreationCombattantsCombatService(),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Le joueur participe déjà à un combat actif.'
        );

        $service->rejoindre(
            42,
            $joueur2,
            $equipeJoueur2,
        );
    }

    public function testRefuseUnCombatIntrouvable(): void
    {
        $joueur = $this->creerJoueurEnregistre(
            2,
            'joueur2@stickverse.test',
        );

        $equipe = $this->creerEquipeEnregistree(
            12,
            $joueur,
            200,
        );

        $entityManager =
            $this->creerEntityManagerTransactionnel();

        $combatRepository = $this->createMock(
            CombatRepository::class
        );

        $combatRepository
            ->method('trouverAvecVerrouEcriture')
            ->willReturn(null);

        $combatRepository
            ->expects(self::never())
            ->method('trouverActifPourJoueur');

        $service = new RejoindreCombatEnLigneService(
            $entityManager,
            $combatRepository,
            new CreationCombattantsCombatService(),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Le combat demandé est introuvable.'
        );

        $service->rejoindre(
            42,
            $joueur,
            $equipe,
        );
    }

    private function creerEntityManagerTransactionnel(
    ): EntityManagerInterface {
        $entityManager = $this->createMock(
            EntityManagerInterface::class
        );

        $entityManager
            ->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(
                static function (
                    callable $operation,
                ): mixed {
                    return $operation();
                }
            );

        return $entityManager;
    }

    private function creerJoueurEnregistre(
        int $id,
        string $email,
    ): User {
        $joueur = new User();
        $joueur->setEmail($email);

        $this->definirId(
            $joueur,
            $id,
        );

        return $joueur;
    }

    private function creerEquipeEnregistree(
        int $id,
        User $joueur,
        int $premierIdStickman,
    ): Equipe {
        $equipe = new Equipe();

        $this->definirId(
            $equipe,
            $id,
        );

        $equipe->setNom('Équipe du joueur');
        $equipe->setUtilisateur($joueur);

        $equipe->setStickmanA(
            $this->creerStickman(
                $premierIdStickman,
                'Guerrier '.$premierIdStickman,
            )
        );

        $equipe->setStickmanB(
            $this->creerStickman(
                $premierIdStickman + 1,
                'Archer '.$premierIdStickman,
            )
        );

        $equipe->setStickmanC(
            $this->creerStickman(
                $premierIdStickman + 2,
                'Lancier '.$premierIdStickman,
            )
        );

        $equipe->setStickmanD(
            $this->creerStickman(
                $premierIdStickman + 3,
                'Gardien '.$premierIdStickman,
            )
        );

        return $equipe;
    }

    private function creerStickman(
        int $id,
        string $nom,
    ): Stickman {
        $stickman = new Stickman();

        $this->definirId(
            $stickman,
            $id,
        );

        $stickman->setNom($nom);
        $stickman->setSlug(
            strtolower(
                str_replace(' ', '-', $nom)
            )
        );
        $stickman->setDescription(
            'Stickman utilisé pour les tests.'
        );
        $stickman->setImage(
            'stickman-'.$id.'.png'
        );
        $stickman->setRarete(1);
        $stickman->setPv(10);
        $stickman->setAttaque(2);
        $stickman->setDefense(1);
        $stickman->setStatutActif(true);

        return $stickman;
    }

    private function definirId(
        object $entite,
        int $id,
    ): void {
        $proprieteId = new ReflectionProperty(
            $entite,
            'id',
        );

        $proprieteId->setValue(
            $entite,
            $id,
        );
    }
}