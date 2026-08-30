<?php

namespace App\Tests\Integration\Service;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Repository\CombattantCombatRepository;
use App\Repository\UserRepository;
use App\Service\CreationCombatEnLigneService;
use App\Service\CreationCombattantsCombatService;
use App\Service\RejoindreCombatEnLigneService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreationEtJonctionCombatMySqlTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CombatRepository $combatRepository;
    private CombattantCombatRepository $combattantRepository;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = static::getContainer()->get(
            EntityManagerInterface::class
        );

        self::assertInstanceOf(
            EntityManagerInterface::class,
            $entityManager,
        );

        $combatRepository = $entityManager->getRepository(
            Combat::class
        );

        $combattantRepository = $entityManager->getRepository(
            CombattantCombat::class
        );

        $userRepository = $entityManager->getRepository(User::class);

        self::assertInstanceOf(
            CombatRepository::class,
            $combatRepository,
        );

        self::assertInstanceOf(
            CombattantCombatRepository::class,
            $combattantRepository,
        );

        self::assertInstanceOf(
            UserRepository::class,
            $userRepository,
        );

        $this->entityManager = $entityManager;
        $this->combatRepository = $combatRepository;
        $this->combattantRepository =
            $combattantRepository;
        $this->userRepository = $userRepository;

        $connexion = $this->entityManager->getConnection();
        $nomBase = $connexion->fetchOne('SELECT DATABASE()');

        if (
            !is_string($nomBase)
            || !str_ends_with($nomBase, '_test')
        ) {
            throw new LogicException(
                'Le test d’intégration doit utiliser une base terminant par "_test".'
            );
        }

        $connexion->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager)) {
            $connexion = $this->entityManager->getConnection();

            if ($connexion->isTransactionActive()) {
                $connexion->rollBack();
            }

            $this->entityManager->clear();
        }

        parent::tearDown();
    }

    public function testCreePuisRejointUnCombatDansMySql(): void
    {
        $suffixe = bin2hex(random_bytes(6));

        $joueur1 = (new User())
            ->setEmail(
                'joueur1-j39-'.$suffixe.'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        $joueur2 = (new User())
            ->setEmail(
                'joueur2-j39-'.$suffixe.'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        $this->entityManager->persist($joueur1);
        $this->entityManager->persist($joueur2);

        $stickmenJoueur1 = [];
        $stickmenJoueur2 = [];

        foreach (['A', 'B', 'C', 'D'] as $slot) {
            $stickmanJoueur1 = $this->creerStickman(
                'J1-'.$slot,
                $suffixe,
            );

            $stickmanJoueur2 = $this->creerStickman(
                'J2-'.$slot,
                $suffixe,
            );

            $stickmenJoueur1[$slot] =
                $stickmanJoueur1;

            $stickmenJoueur2[$slot] =
                $stickmanJoueur2;

            $this->entityManager->persist(
                $stickmanJoueur1
            );

            $this->entityManager->persist(
                $stickmanJoueur2
            );
        }

        /*
         * Les Stickmans doivent recevoir leurs identifiants
         * avant la création des snapshots.
         */
        $this->entityManager->flush();

        $equipeJoueur1 = $this->creerEquipe(
            'Équipe J39 joueur 1',
            $joueur1,
            $stickmenJoueur1,
        );

        $equipeJoueur2 = $this->creerEquipe(
            'Équipe J39 joueur 2',
            $joueur2,
            $stickmenJoueur2,
        );

        $this->entityManager->persist(
            $equipeJoueur1
        );

        $this->entityManager->persist(
            $equipeJoueur2
        );

        $this->entityManager->flush();

        $joueur1Id = $joueur1->getId();
        $joueur2Id = $joueur2->getId();
        $equipeJoueur2Id = $equipeJoueur2->getId();

        self::assertNotNull($joueur1Id);
        self::assertNotNull($joueur2Id);
        self::assertNotNull($equipeJoueur2Id);

        $creationCombattantsService =
            new CreationCombattantsCombatService();

        $creationCombatService =
            new CreationCombatEnLigneService(
                $this->entityManager,
                $this->combatRepository,
                $this->userRepository,
                $creationCombattantsService,
            );

        $rejoindreCombatService =
            new RejoindreCombatEnLigneService(
                $this->entityManager,
                $this->combatRepository,
                $this->userRepository,
                $creationCombattantsService,
            );

        /*
         * Création par le joueur 1.
         */
        $combat = $creationCombatService->creer(
            $joueur1,
            $equipeJoueur1,
        );

        $combatId = $combat->getId();

        self::assertNotNull($combatId);

        /*
         * On vide Doctrine pour vérifier les données réellement
         * enregistrées dans MySQL.
         */
        $this->entityManager->clear();

        $combatEnAttente = $this->combatRepository->find(
            $combatId
        );

        self::assertInstanceOf(
            Combat::class,
            $combatEnAttente,
        );

        self::assertSame(
            Combat::STATUT_EN_ATTENTE,
            $combatEnAttente->getStatut(),
        );

        self::assertSame(
            1,
            $combatEnAttente->getNumeroRound(),
        );

        self::assertSame(
            $joueur1Id,
            $combatEnAttente->getJoueur1()->getId(),
        );

        self::assertNull(
            $combatEnAttente->getJoueur2()
        );

        self::assertCount(
            4,
            $combatEnAttente->getCombattants(),
        );

        self::assertSame(
            ['A', 'B', 'C', 'D'],
            $this->lireSlots(
                $combatEnAttente,
                $combatEnAttente->getJoueur1(),
            ),
        );

        /*
         * Les entités utilisées pour rejoindre le combat sont
         * rechargées après le clear Doctrine.
         */
        $joueur2Recharge = $this->entityManager->find(
            User::class,
            $joueur2Id,
        );

        $equipeJoueur2Recharge =
            $this->entityManager->find(
                Equipe::class,
                $equipeJoueur2Id,
            );

        self::assertInstanceOf(
            User::class,
            $joueur2Recharge,
        );

        self::assertInstanceOf(
            Equipe::class,
            $equipeJoueur2Recharge,
        );

        /*
         * Le joueur 2 rejoint le combat.
         */
        $combatRejoint = $rejoindreCombatService
            ->rejoindre(
                $combatId,
                $joueur2Recharge,
                $equipeJoueur2Recharge,
            );

        self::assertSame(
            Combat::STATUT_EN_COURS,
            $combatRejoint->getStatut(),
        );

        self::assertSame(
            $joueur2Id,
            $combatRejoint->getJoueur2()?->getId(),
        );

        /*
         * Deuxième clear Doctrine pour vérifier l’état final
         * enregistré dans MySQL.
         */
        $this->entityManager->clear();

        $combatEnCours = $this->combatRepository->find(
            $combatId
        );

        self::assertInstanceOf(
            Combat::class,
            $combatEnCours,
        );

        self::assertSame(
            Combat::STATUT_EN_COURS,
            $combatEnCours->getStatut(),
        );

        self::assertSame(
            1,
            $combatEnCours->getNumeroRound(),
        );

        self::assertSame(
            $joueur1Id,
            $combatEnCours->getJoueur1()->getId(),
        );

        self::assertSame(
            $joueur2Id,
            $combatEnCours->getJoueur2()?->getId(),
        );

        self::assertCount(
            8,
            $combatEnCours->getCombattants(),
        );

        $joueur1Final = $combatEnCours->getJoueur1();
        $joueur2Final = $combatEnCours->getJoueur2();

        self::assertInstanceOf(
            User::class,
            $joueur2Final,
        );

        self::assertSame(
            ['A', 'B', 'C', 'D'],
            $this->lireSlots(
                $combatEnCours,
                $joueur1Final,
            ),
        );

        self::assertSame(
            ['A', 'B', 'C', 'D'],
            $this->lireSlots(
                $combatEnCours,
                $joueur2Final,
            ),
        );

        $combatActifJoueur1 = $this->combatRepository
            ->trouverActifPourJoueur($joueur1Final);

        $combatActifJoueur2 = $this->combatRepository
            ->trouverActifPourJoueur($joueur2Final);

        self::assertInstanceOf(
            Combat::class,
            $combatActifJoueur1,
        );

        self::assertInstanceOf(
            Combat::class,
            $combatActifJoueur2,
        );

        self::assertSame(
            $combatId,
            $combatActifJoueur1->getId(),
        );

        self::assertSame(
            $combatId,
            $combatActifJoueur2->getId(),
        );
    }

    private function creerStickman(
        string $nomCourt,
        string $suffixe,
    ): Stickman {
        $slug = strtolower(
            str_replace('-', '-', $nomCourt)
        );

        return (new Stickman())
            ->setNom(
                'Stickman '.$nomCourt
            )
            ->setSlug(
                'stickman-j39-'.$slug.'-'.$suffixe
            )
            ->setDescription(
                'Stickman utilisé par le test J39.'
            )
            ->setImage(
                'stickman-j39-'.$slug.'.png'
            )
            ->setRarete(1)
            ->setPv(10)
            ->setAttaque(2)
            ->setDefense(1)
            ->setStatutActif(true);
    }

    /**
     * @param array<string, Stickman> $stickmen
     */
    private function creerEquipe(
        string $nom,
        User $joueur,
        array $stickmen,
    ): Equipe {
        return (new Equipe())
            ->setNom($nom)
            ->setUtilisateur($joueur)
            ->setStickmanA($stickmen['A'])
            ->setStickmanB($stickmen['B'])
            ->setStickmanC($stickmen['C'])
            ->setStickmanD($stickmen['D']);
    }

    /**
     * @return list<string>
     */
    private function lireSlots(
        Combat $combat,
        User $joueur,
    ): array {
        $combattants = $this->combattantRepository
            ->trouverPourCombatEtJoueur(
                $combat,
                $joueur,
            );

        $slots = array_map(
            static fn (
                CombattantCombat $combattant,
            ): string => $combattant->getSlot(),
            $combattants,
        );

        sort($slots);

        return $slots;
    }
}
