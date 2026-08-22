<?php

namespace App\Tests\Integration\Service;

use App\Entity\Combat;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Service\AbandonCombatService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AbandonCombatMySqlTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CombatRepository $combatRepository;

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

        self::assertInstanceOf(
            CombatRepository::class,
            $combatRepository,
        );

        $this->entityManager = $entityManager;
        $this->combatRepository = $combatRepository;

        /*
         * Ce garde-fou empêche ce test destructif d’utiliser
         * accidentellement la base de développement.
         */
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

        /*
         * Toutes les données créées par le test seront annulées
         * dans tearDown().
         */
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

    public function testEnregistreLabandonEtLeGagnantDansMySql(): void
    {
        $suffixe = bin2hex(random_bytes(6));

        $joueur1 = (new User())
            ->setEmail(
                'abandon-joueur1-'.$suffixe.'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        $joueur2 = (new User())
            ->setEmail(
                'abandon-joueur2-'.$suffixe.'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        $this->entityManager->persist($joueur1);
        $this->entityManager->persist($joueur2);

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $this->entityManager->persist($combat);
        $this->entityManager->flush();

        $combatId = $combat->getId();
        $joueur2Id = $joueur2->getId();

        self::assertNotNull($combatId);
        self::assertNotNull($joueur2Id);

        $service = new AbandonCombatService(
            $this->entityManager,
            $this->combatRepository,
        );

        /*
         * Le joueur 1 abandonne.
         * Le joueur 2 doit devenir le gagnant.
         */
        $service->abandonner(
            combatId: $combatId,
            joueur: $joueur1,
        );

        /*
         * On vide entièrement l’EntityManager.
         *
         * Le prochain Combat sera obligatoirement relu
         * depuis MySQL et non depuis la mémoire PHP.
         */
        $this->entityManager->clear();

        $combatRecharge = $this->combatRepository->find(
            $combatId
        );

        self::assertInstanceOf(
            Combat::class,
            $combatRecharge,
        );

        self::assertSame(
            Combat::STATUT_ABANDONNE,
            $combatRecharge->getStatut(),
        );

        self::assertSame(
            1,
            $combatRecharge->getNumeroRound(),
        );

        $gagnantRecharge = $combatRecharge->getGagnant();

        self::assertInstanceOf(
            User::class,
            $gagnantRecharge,
        );

        self::assertSame(
            $joueur2Id,
            $gagnantRecharge->getId(),
        );
    }
}