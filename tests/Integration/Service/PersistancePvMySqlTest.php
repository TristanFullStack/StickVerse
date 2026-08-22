<?php

namespace App\Tests\Integration\Service;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\PlanRoundCombat;
use App\Entity\Stickman;
use App\Entity\User;
use App\Model\PlanCombat;
use App\Repository\CombatRepository;
use App\Repository\CombattantCombatRepository;
use App\Repository\PlanRoundCombatRepository;
use App\Service\CombatService;
use App\Service\CreationEtatEquipeCombatDepuisSnapshotsService;
use App\Service\DeterminationFinCombatService;
use App\Service\ResolutionRoundCombatEnLigneService;
use App\Service\ResolutionRoundService;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class PersistancePvMySqlTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private CombatRepository $combatRepository;
    private CombattantCombatRepository $combattantRepository;
    private ResolutionRoundCombatEnLigneService $resolutionService;

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

        $planRepository = $entityManager->getRepository(
            PlanRoundCombat::class
        );

        self::assertInstanceOf(
            CombatRepository::class,
            $combatRepository,
        );

        self::assertInstanceOf(
            CombattantCombatRepository::class,
            $combattantRepository,
        );

        self::assertInstanceOf(
            PlanRoundCombatRepository::class,
            $planRepository,
        );

        $this->entityManager = $entityManager;
        $this->combatRepository = $combatRepository;
        $this->combattantRepository = $combattantRepository;

        /*
         * Le service est construit manuellement parce qu’il n’est
         * pas encore utilisé par un contrôleur de production.
         *
         * Ses dépendances Doctrine sont cependant bien réelles.
         */
        $this->resolutionService =
            new ResolutionRoundCombatEnLigneService(
                $entityManager,
                $combatRepository,
                $planRepository,
                $combattantRepository,
                new CreationEtatEquipeCombatDepuisSnapshotsService(),
                new ResolutionRoundService(
                    new CombatService()
                ),
                new DeterminationFinCombatService(),
            );

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

    public function testConserveReellementLesPvDansMySql(): void
    {
        $suffixe = bin2hex(random_bytes(6));

        $joueur1 = (new User())
            ->setEmail('joueur1-j36-'.$suffixe.'@example.com')
            ->setPassword('mot-de-passe-test');

        $joueur2 = (new User())
            ->setEmail('joueur2-j36-'.$suffixe.'@example.com')
            ->setPassword('mot-de-passe-test');

        $this->entityManager->persist($joueur1);
        $this->entityManager->persist($joueur2);

        $stickmen = [];

        foreach (['A', 'B', 'C', 'D'] as $slot) {
            $stickman = $this->creerStickman($slot, $suffixe);

            $stickmen[$slot] = $stickman;
            $this->entityManager->persist($stickman);
        }

        /*
         * Les Stickmans doivent posséder un identifiant avant
         * la création des snapshots.
         */
        $this->entityManager->flush();

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        foreach ([$joueur1, $joueur2] as $joueur) {
            foreach (['A', 'B', 'C', 'D'] as $slot) {
                new CombattantCombat(
                    $combat,
                    $joueur,
                    $slot,
                    $stickmen[$slot],
                );
            }
        }

        $this->creerPlans(
            $combat,
            $joueur1,
            $joueur2,
        );

        $this->entityManager->persist($combat);
        $this->entityManager->flush();

        $combatId = $combat->getId();

        self::assertNotNull($combatId);

        /*
         * ROUND 1
         *
         * Les groupes X et Y possèdent chacun 2 points
         * d’attaque et aucune défense ne protège A ou B.
         *
         * A et B passent donc de 10 à 8 PV.
         */
        $resultatRound1 = $this->resolutionService
            ->resoudreSiPret($combatId);

        self::assertNotNull($resultatRound1);

        /*
         * On vide complètement la mémoire de Doctrine.
         *
         * Les prochaines valeurs ne peuvent donc plus venir
         * des objets PHP déjà chargés : elles seront relues
         * directement depuis MySQL.
         */
        $this->entityManager->clear();

        $combatRound2 = $this->combatRepository->find($combatId);

        self::assertInstanceOf(Combat::class, $combatRound2);
        self::assertSame(2, $combatRound2->getNumeroRound());

        $joueur1Round2 = $combatRound2->getJoueur1();
        $joueur2Round2 = $combatRound2->getJoueur2();

        self::assertInstanceOf(User::class, $joueur2Round2);

        self::assertSame(
            [
                'A' => 8,
                'B' => 8,
                'C' => 10,
                'D' => 10,
            ],
            $this->lirePvDepuisMySql(
                $combatRound2,
                $joueur1Round2,
            ),
        );

        self::assertSame(
            [
                'A' => 8,
                'B' => 8,
                'C' => 10,
                'D' => 10,
            ],
            $this->lirePvDepuisMySql(
                $combatRound2,
                $joueur2Round2,
            ),
        );

        /*
         * Les plans sont créés à partir du combat rechargé.
         * Le constructeur copie donc bien le numéro 2.
         */
        $this->creerPlans(
            $combatRound2,
            $joueur1Round2,
            $joueur2Round2,
        );

        $this->entityManager->flush();

        /*
         * ROUND 2
         *
         * La résolution doit repartir des 8 PV enregistrés
         * en base, et non des 10 PV maximum.
         */
        $resultatRound2 = $this->resolutionService
            ->resoudreSiPret($combatId);

        self::assertNotNull($resultatRound2);

        self::assertSame(
            8,
            $resultatRound2['joueur1_A']['pvAvant'],
        );

        self::assertSame(
            6,
            $resultatRound2['joueur1_A']['pvRestants'],
        );

        self::assertSame(
            8,
            $resultatRound2['joueur2_A']['pvAvant'],
        );

        self::assertSame(
            6,
            $resultatRound2['joueur2_A']['pvRestants'],
        );

        /*
         * Deuxième vidage de l’EntityManager :
         * vérification finale depuis MySQL.
         */
        $this->entityManager->clear();

        $combatRound3 = $this->combatRepository->find($combatId);

        self::assertInstanceOf(Combat::class, $combatRound3);
        self::assertSame(3, $combatRound3->getNumeroRound());

        $joueur1Round3 = $combatRound3->getJoueur1();
        $joueur2Round3 = $combatRound3->getJoueur2();

        self::assertInstanceOf(User::class, $joueur2Round3);

        self::assertSame(
            [
                'A' => 6,
                'B' => 6,
                'C' => 10,
                'D' => 10,
            ],
            $this->lirePvDepuisMySql(
                $combatRound3,
                $joueur1Round3,
            ),
        );

        self::assertSame(
            [
                'A' => 6,
                'B' => 6,
                'C' => 10,
                'D' => 10,
            ],
            $this->lirePvDepuisMySql(
                $combatRound3,
                $joueur2Round3,
            ),
        );
    }

    private function creerStickman(
        string $slot,
        string $suffixe,
    ): Stickman {
        return (new Stickman())
            ->setNom('Stickman J36 '.$slot)
            ->setSlug(
                'stickman-j36-'.strtolower($slot).'-'.$suffixe
            )
            ->setDescription(
                'Stickman utilisé par le test d’intégration J36.'
            )
            ->setImage(
                'stickman-j36-'.strtolower($slot).'.png'
            )
            ->setRarete(1)
            ->setPv(10)
            ->setAttaque(1)
            ->setDefense(0)
            ->setStatutActif(true);
    }

    private function creerPlans(
        Combat $combat,
        User $joueur1,
        User $joueur2,
    ): void {
        $planJoueur1 = new PlanRoundCombat(
            $combat,
            $joueur1,
            new PlanCombat('A', 'B', 'C', 'D'),
        );

        $planJoueur2 = new PlanRoundCombat(
            $combat,
            $joueur2,
            new PlanCombat('A', 'B', 'C', 'D'),
        );

        $this->entityManager->persist($planJoueur1);
        $this->entityManager->persist($planJoueur2);
    }

    /**
     * @return array<string, int>
     */
    private function lirePvDepuisMySql(
        Combat $combat,
        User $joueur,
    ): array {
        $pvParSlot = [];

        $combattants = $this->combattantRepository
            ->trouverPourCombatEtJoueur(
                $combat,
                $joueur,
            );

        foreach ($combattants as $combattant) {
            $pvParSlot[$combattant->getSlot()] =
                $combattant->getPvActuels();
        }

        ksort($pvParSlot);

        return $pvParSlot;
    }
}