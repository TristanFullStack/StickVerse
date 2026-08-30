<?php

namespace App\Tests\Service;

use App\Entity\Combat;
use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Service\CreationCombatEnLigneService;
use App\Service\CreationCombattantsCombatService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

final class CreationCombatEnLigneServiceTest extends TestCase
{
    public function testCreeUnCombatEnAttenteAvecSnapshots(): void
    {
        $joueur = $this->creerJoueurEnregistre(
            1,
            'joueur1@stickverse.test',
        );

        $equipe = $this->creerEquipeEnregistree(
            10,
            $joueur,
        );

        $entityManager =
            $this->creerEntityManagerTransactionnel();

        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(
                self::callback(
                    static function (
                        object $entite,
                    ) use (
                        $joueur,
                    ): bool {
                        self::assertInstanceOf(
                            Combat::class,
                            $entite,
                        );

                        self::assertSame(
                            $joueur,
                            $entite->getJoueur1(),
                        );

                        self::assertNull(
                            $entite->getJoueur2()
                        );

                        self::assertSame(
                            Combat::STATUT_EN_ATTENTE,
                            $entite->getStatut(),
                        );

                        self::assertSame(
                            1,
                            $entite->getNumeroRound(),
                        );

                        self::assertMatchesRegularExpression(
                            '/^SV-[A-F0-9]{6}$/',
                            (string) $entite->getCodeInvitation(),
                        );

                        self::assertCount(
                            4,
                            $entite->getCombattants(),
                        );

                        return true;
                    }
                )
            );

        $combatRepository = $this->createStub(
            CombatRepository::class
        );

        $combatRepository
            ->method('trouverActifPourJoueur')
            ->willReturn(null);

        $service = new CreationCombatEnLigneService(
            $entityManager,
            $combatRepository,
            new CreationCombattantsCombatService(),
        );

        $combat = $service->creer(
            $joueur,
            $equipe,
        );

        self::assertSame(
            $joueur,
            $combat->getJoueur1(),
        );

        self::assertNull(
            $combat->getJoueur2()
        );

        self::assertSame(
            Combat::STATUT_EN_ATTENTE,
            $combat->getStatut(),
        );

        self::assertSame(
            1,
            $combat->getNumeroRound(),
        );

        self::assertMatchesRegularExpression(
            '/^SV-[A-F0-9]{6}$/',
            (string) $combat->getCodeInvitation(),
        );

        self::assertCount(
            4,
            $combat->getCombattants(),
        );

        foreach (
            $combat->getCombattants()
            as $combattant
        ) {
            self::assertSame(
                $joueur,
                $combattant->getJoueur(),
            );

            self::assertSame(
                $combat,
                $combattant->getCombat(),
            );
        }
    }

    public function testRefuseUnJoueurDejaDansUnCombatActif(): void
    {
        $joueur = $this->creerJoueurEnregistre(
            1,
            'joueur1@stickverse.test',
        );

        $equipe = $this->creerEquipeEnregistree(
            10,
            $joueur,
        );

        $combatExistant = new Combat($joueur);

        $entityManager =
            $this->creerEntityManagerTransactionnel();

        $entityManager
            ->expects(self::never())
            ->method('persist');

        $combatRepository = $this->createStub(
            CombatRepository::class
        );

        $combatRepository
            ->method('trouverActifPourJoueur')
            ->willReturn($combatExistant);

        $service = new CreationCombatEnLigneService(
            $entityManager,
            $combatRepository,
            new CreationCombattantsCombatService(),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Le joueur participe déjà à un combat actif.'
        );

        $service->creer(
            $joueur,
            $equipe,
        );
    }

    public function testRefuseUnJoueurNonEnregistre(): void
    {
        $joueur = new User();

        $equipe = new Equipe();

        $entityManager =
            $this->creerEntityManagerTransactionnel();

        $entityManager
            ->expects(self::never())
            ->method('persist');

        $combatRepository = $this->createMock(
            CombatRepository::class
        );

        $combatRepository
            ->expects(self::never())
            ->method('trouverActifPourJoueur');

        $service = new CreationCombatEnLigneService(
            $entityManager,
            $combatRepository,
            new CreationCombattantsCombatService(),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Le joueur doit être enregistré en base de données.'
        );

        $service->creer(
            $joueur,
            $equipe,
        );
    }

    public function testRefuseUneEquipeNonEnregistree(): void
    {
        $joueur = $this->creerJoueurEnregistre(
            1,
            'joueur1@stickverse.test',
        );

        $equipe = new Equipe();
        $equipe->setUtilisateur($joueur);

        $entityManager =
            $this->creerEntityManagerTransactionnel();

        $entityManager
            ->expects(self::never())
            ->method('persist');

        $combatRepository = $this->createMock(
            CombatRepository::class
        );

        $combatRepository
            ->expects(self::never())
            ->method('trouverActifPourJoueur');

        $service = new CreationCombatEnLigneService(
            $entityManager,
            $combatRepository,
            new CreationCombattantsCombatService(),
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'L’équipe doit être enregistrée en base de données.'
        );

        $service->creer(
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
    ): Equipe {
        $equipe = new Equipe();

        $this->definirId(
            $equipe,
            $id,
        );

        $equipe->setNom('Équipe principale');
        $equipe->setUtilisateur($joueur);

        $equipe->setStickmanA(
            $this->creerStickman(
                101,
                'Guerrier',
            )
        );

        $equipe->setStickmanB(
            $this->creerStickman(
                102,
                'Archer',
            )
        );

        $equipe->setStickmanC(
            $this->creerStickman(
                103,
                'Lancier',
            )
        );

        $equipe->setStickmanD(
            $this->creerStickman(
                104,
                'Gardien',
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
        $stickman->setSlug(strtolower($nom));
        $stickman->setDescription(
            'Stickman utilisé pour les tests.'
        );
        $stickman->setImage(
            strtolower($nom).'.png'
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
