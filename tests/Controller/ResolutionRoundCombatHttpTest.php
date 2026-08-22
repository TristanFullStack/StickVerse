<?php

namespace App\Tests\Controller;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\CombattantCombatRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ResolutionRoundCombatHttpTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $entityManager = static::getContainer()->get(
            EntityManagerInterface::class
        );

        self::assertInstanceOf(
            EntityManagerInterface::class,
            $entityManager,
        );

        $this->entityManager = $entityManager;

        $connexion = $this->entityManager->getConnection();
        $nomBase = $connexion->fetchOne('SELECT DATABASE()');

        if (
            !is_string($nomBase)
            || !str_ends_with($nomBase, '_test')
        ) {
            throw new LogicException(
                'Le test HTTP doit utiliser une base terminant par "_test".'
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

    public function testDeuxParticipantsResolventUnRoundViaHttp(): void
    {
        [
            $combat,
            $joueur1,
            $joueur2,
        ] = $this->creerCombatAvecSnapshots();

        $combatId = $combat->getId();
        $joueur1Id = $joueur1->getId();
        $joueur2Id = $joueur2->getId();

        self::assertNotNull($combatId);
        self::assertNotNull($joueur1Id);
        self::assertNotNull($joueur2Id);

        /*
         * Première session simulée :
         * le joueur 1 soumet son plan.
         */
        $this->client->loginUser($joueur1);

        $this->client->request(
            'GET',
            '/combat-en-ligne/'.$combatId,
        );

        self::assertResponseIsSuccessful();

        $etatJoueur1 = $this->lireReponseJson();

        self::assertFalse(
            $etatJoueur1['planSoumis']
        );

        self::assertFalse(
            $etatJoueur1['adversairePret']
        );

        self::assertIsArray(
            $etatJoueur1['csrf']
        );

        self::assertIsString(
            $etatJoueur1['csrf']['plan']
        );

        $this->client->jsonRequest(
            'POST',
            '/combat-en-ligne/'.$combatId.'/plan',
            [
                'cibleAttaqueX' => 'A',
                'cibleAttaqueY' => 'B',
                'cibleDefenseX' => 'C',
                'cibleDefenseY' => 'D',
            ],
            [
                'HTTP_X_CSRF_TOKEN' =>
                    $etatJoueur1['csrf']['plan'],
            ],
        );

        self::assertResponseStatusCodeSame(
            Response::HTTP_CREATED
        );

        $attenteJoueur1 = $this->lireReponseJson();

        self::assertSame(
            'en_attente_adversaire',
            $attenteJoueur1['etat'],
        );

        self::assertSame(
            1,
            $attenteJoueur1['numeroRound'],
        );

        self::assertArrayNotHasKey(
            'resultats',
            $attenteJoueur1,
        );

        /*
         * Deuxième session simulée :
         * le joueur 2 consulte l’état.
         *
         * Il sait que l’adversaire est prêt, mais il ne reçoit
         * jamais le contenu du plan du joueur 1.
         */
        $this->client->loginUser($joueur2);

        $this->client->request(
            'GET',
            '/combat-en-ligne/'.$combatId,
        );

        self::assertResponseIsSuccessful();

        $etatJoueur2 = $this->lireReponseJson();

        self::assertFalse(
            $etatJoueur2['planSoumis']
        );

        self::assertTrue(
            $etatJoueur2['adversairePret']
        );

        self::assertArrayNotHasKey(
            'plans',
            $etatJoueur2,
        );

        self::assertArrayNotHasKey(
            'resultats',
            $etatJoueur2,
        );

        self::assertIsArray(
            $etatJoueur2['csrf']
        );

        self::assertIsString(
            $etatJoueur2['csrf']['plan']
        );

        /*
         * Le deuxième plan déclenche la résolution atomique.
         */
        $this->client->jsonRequest(
            'POST',
            '/combat-en-ligne/'.$combatId.'/plan',
            [
                'cibleAttaqueX' => 'A',
                'cibleAttaqueY' => 'B',
                'cibleDefenseX' => 'C',
                'cibleDefenseY' => 'D',
            ],
            [
                'HTTP_X_CSRF_TOKEN' =>
                    $etatJoueur2['csrf']['plan'],
            ],
        );

        self::assertResponseStatusCodeSame(
            Response::HTTP_OK
        );

        $resolution = $this->lireReponseJson();

        self::assertSame(
            'round_resolu',
            $resolution['etat'],
        );

        self::assertSame(
            Combat::STATUT_EN_COURS,
            $resolution['statut'],
        );

        self::assertSame(
            2,
            $resolution['numeroRound'],
        );

        self::assertNull(
            $resolution['gagnantId']
        );

        self::assertArrayHasKey(
            'resultats',
            $resolution,
        );

        self::assertIsArray(
            $resolution['resultats']
        );

        self::assertSame(
            10,
            $resolution['resultats']['joueur1_A']['pvAvant'],
        );

        self::assertSame(
            8,
            $resolution['resultats']['joueur1_A']['pvRestants'],
        );

        self::assertSame(
            10,
            $resolution['resultats']['joueur2_A']['pvAvant'],
        );

        self::assertSame(
            8,
            $resolution['resultats']['joueur2_A']['pvRestants'],
        );

        /*
         * Doctrine est vidé pour forcer une relecture depuis MySQL.
         */
        $this->entityManager->clear();

        $combatRecharge = $this->entityManager->find(
            Combat::class,
            $combatId,
        );

        self::assertInstanceOf(
            Combat::class,
            $combatRecharge,
        );

        self::assertSame(
            Combat::STATUT_EN_COURS,
            $combatRecharge->getStatut(),
        );

        self::assertSame(
            2,
            $combatRecharge->getNumeroRound(),
        );

        $joueur1Recharge = $combatRecharge->getJoueur1();
        $joueur2Recharge = $combatRecharge->getJoueur2();

        self::assertSame(
            $joueur1Id,
            $joueur1Recharge->getId(),
        );

        self::assertInstanceOf(
            User::class,
            $joueur2Recharge,
        );

        self::assertSame(
            $joueur2Id,
            $joueur2Recharge->getId(),
        );

        self::assertSame(
            [
                'A' => 8,
                'B' => 8,
                'C' => 10,
                'D' => 10,
            ],
            $this->lirePvDepuisMySql(
                $combatRecharge,
                $joueur1Recharge,
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
                $combatRecharge,
                $joueur2Recharge,
            ),
        );
    }

    /**
     * @return array{Combat, User, User}
     */
    private function creerCombatAvecSnapshots(): array
    {
        $suffixe = bin2hex(random_bytes(6));

        $joueur1 = (new User())
            ->setEmail(
                'joueur1-j38-http-'.$suffixe.'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        $joueur2 = (new User())
            ->setEmail(
                'joueur2-j38-http-'.$suffixe.'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        $this->entityManager->persist($joueur1);
        $this->entityManager->persist($joueur2);

        $stickmen = [];

        foreach (['A', 'B', 'C', 'D'] as $slot) {
            $stickman = $this->creerStickman(
                $slot,
                $suffixe,
            );

            $stickmen[$slot] = $stickman;

            $this->entityManager->persist($stickman);
        }

        /*
         * Les Stickmans doivent recevoir leur identifiant avant
         * d’être copiés dans les snapshots du combat.
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

        $this->entityManager->persist($combat);
        $this->entityManager->flush();

        return [
            $combat,
            $joueur1,
            $joueur2,
        ];
    }

    private function creerStickman(
        string $slot,
        string $suffixe,
    ): Stickman {
        return (new Stickman())
            ->setNom(
                'Stickman J38 HTTP '.$slot
            )
            ->setSlug(
                'stickman-j38-http-'
                .strtolower($slot)
                .'-'
                .$suffixe
            )
            ->setDescription(
                'Stickman utilisé par le test HTTP J38.'
            )
            ->setImage(
                'stickman-j38-http-'
                .strtolower($slot)
                .'.png'
            )
            ->setRarete(1)
            ->setPv(10)
            ->setAttaque(1)
            ->setDefense(0)
            ->setStatutActif(true);
    }

    /**
     * @return array<string, int>
     */
    private function lirePvDepuisMySql(
        Combat $combat,
        User $joueur,
    ): array {
        $combattantRepository = $this->entityManager
            ->getRepository(CombattantCombat::class);

        self::assertInstanceOf(
            CombattantCombatRepository::class,
            $combattantRepository,
        );

        $combattants = $combattantRepository
            ->trouverPourCombatEtJoueur(
                $combat,
                $joueur,
            );

        $pvParSlot = [];

        foreach ($combattants as $combattant) {
            $pvParSlot[$combattant->getSlot()] =
                $combattant->getPvActuels();
        }

        ksort($pvParSlot);

        return $pvParSlot;
    }

    /**
     * @return array<string, mixed>
     */
    private function lireReponseJson(): array
    {
        $contenu = $this->client
            ->getResponse()
            ->getContent();

        if (!is_string($contenu)) {
            self::fail(
                'La réponse HTTP ne contient pas de JSON.'
            );
        }

        $donnees = json_decode(
            $contenu,
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertIsArray($donnees);

        return $donnees;
    }
}