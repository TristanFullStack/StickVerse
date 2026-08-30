<?php

namespace App\Tests\Controller;

use App\Entity\CombattantCombat;
use App\Entity\Combat;
use App\Entity\PlanRoundCombat;
use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\PlanRoundCombatRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CombatEnLigneControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        /*
         * Plusieurs requêtes HTTP sont effectuées dans certains tests.
         *
         * Le redémarrage du kernel entre deux requêtes fermerait
         * la connexion Doctrine contenant la transaction de test.
         */
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

    public function testParticipantConsulteEtatSansVoirLesPlans(): void
    {
        [
            $combat,
            $joueur1,
            $joueur2,
        ] = $this->creerCombat();

        $combatId = $combat->getId();

        self::assertNotNull($combatId);

        $this->client->loginUser($joueur1);

        $this->client->request(
            'GET',
            '/combat-en-ligne/'.$combatId,
        );

        self::assertResponseIsSuccessful();

        $donnees = $this->lireReponseJson();

        self::assertSame(
            $combatId,
            $donnees['combatId'],
        );

        self::assertSame(
            Combat::STATUT_EN_COURS,
            $donnees['statut'],
        );

        self::assertSame(
            1,
            $donnees['numeroRound'],
        );

        self::assertNull($donnees['gagnantId']);
        self::assertNull($donnees['dernierRound']);
        self::assertSame([], $donnees['historiqueRounds']);
        self::assertFalse($donnees['planSoumis']);
        self::assertFalse($donnees['adversairePret']);
        self::assertFalse($donnees['preparation']['active']);
        self::assertTrue($donnees['preparation']['moiPret']);
        self::assertTrue($donnees['preparation']['adversairePret']);
        self::assertNull($donnees['preparation']['expiration']);
        self::assertFalse($donnees['forfaitAutomatique']);
        self::assertFalse($donnees['forfaitPreparationAutomatique']);
        self::assertFalse($donnees['annulationPreparationAutomatique']);
        self::assertNull($donnees['expirationPlan']);

        self::assertSame(
            $joueur1->getId(),
            $donnees['moi']['id'],
        );

        self::assertSame(
            $joueur1->getEmail(),
            $donnees['moi']['email'],
        );

        self::assertCount(
            4,
            $donnees['moi']['combattants'],
        );

        self::assertSame(
            ['A', 'B', 'C', 'D'],
            array_column(
                $donnees['moi']['combattants'],
                'slot',
            ),
        );

        self::assertSame(
            'Stickman J1 A',
            $donnees['moi']['combattants'][0]['nom'],
        );

        self::assertSame(
            'stickman-j1-a.png',
            $donnees['moi']['combattants'][0]['image'],
        );

        self::assertSame(
            1,
            $donnees['moi']['combattants'][0]['rarete'],
        );

        self::assertSame(
            10,
            $donnees['moi']['combattants'][0]['pvMaximum'],
        );

        self::assertSame(
            10,
            $donnees['moi']['combattants'][0]['pvActuels'],
        );

        self::assertSame(
            2,
            $donnees['moi']['combattants'][0]['attaque'],
        );

        self::assertSame(
            1,
            $donnees['moi']['combattants'][0]['defense'],
        );

        self::assertTrue(
            $donnees['moi']['combattants'][0]['vivant'],
        );

        self::assertIsInt(
            $donnees['moi']['combattants'][0]['stickmanIdOriginal'],
        );

        self::assertSame(
            $joueur2->getId(),
            $donnees['adversaire']['id'],
        );

        self::assertSame(
            $joueur2->getEmail(),
            $donnees['adversaire']['email'],
        );

        self::assertCount(
            4,
            $donnees['adversaire']['combattants'],
        );

        self::assertSame(
            ['A', 'B', 'C', 'D'],
            array_column(
                $donnees['adversaire']['combattants'],
                'slot',
            ),
        );

        self::assertSame(
            'Stickman J2 A',
            $donnees['adversaire']['combattants'][0]['nom'],
        );

        self::assertSame(
            12,
            $donnees['adversaire']['combattants'][0]['pvMaximum'],
        );

        self::assertSame(
            12,
            $donnees['adversaire']['combattants'][0]['pvActuels'],
        );

        self::assertTrue(
            $donnees['adversaire']['combattants'][0]['vivant'],
        );

        self::assertSame(
            3,
            $donnees['adversaire']['combattants'][0]['attaque'],
        );

        self::assertSame(
            2,
            $donnees['adversaire']['combattants'][0]['defense'],
        );

        self::assertArrayHasKey('csrf', $donnees);
        self::assertIsArray($donnees['csrf']);

        self::assertArrayHasKey(
            'plan',
            $donnees['csrf'],
        );

        self::assertArrayHasKey(
            'abandon',
            $donnees['csrf'],
        );

        self::assertArrayHasKey(
            'pret',
            $donnees['csrf'],
        );

        self::assertIsString(
            $donnees['csrf']['plan']
        );

        self::assertIsString(
            $donnees['csrf']['abandon']
        );

        self::assertIsString(
            $donnees['csrf']['pret']
        );

        self::assertNotSame(
            '',
            $donnees['csrf']['plan'],
        );

        self::assertNotSame(
            '',
            $donnees['csrf']['abandon'],
        );

        self::assertArrayNotHasKey(
            'plans',
            $donnees,
        );

        self::assertArrayNotHasKey(
            'resultats',
            $donnees,
        );

        $contenuJson = json_encode(
            $donnees,
            JSON_THROW_ON_ERROR,
        );

        self::assertStringNotContainsString(
            'cibleAttaqueX',
            $contenuJson,
        );

        self::assertStringNotContainsString(
            'cibleDefenseX',
            $contenuJson,
        );

        self::assertStringNotContainsString(
            'cibleAttaqueY',
            $contenuJson,
        );

        self::assertStringNotContainsString(
            'cibleDefenseY',
            $contenuJson,
        );
    }

    public function testRefuseLaConsultationParUnIntrus(): void
    {
        [
            $combat,
            ,
            ,
            $intrus,
        ] = $this->creerCombat();

        $combatId = $combat->getId();

        self::assertNotNull($combatId);

        $this->client->loginUser($intrus);

        $this->client->request(
            'GET',
            '/combat-en-ligne/'.$combatId,
        );

        self::assertResponseStatusCodeSame(
            Response::HTTP_FORBIDDEN
        );
    }

    public function testEnregistreUnPlanValideSansLexposer(): void
    {
        [
            $combat,
            $joueur1,
        ] = $this->creerCombat();

        $combatId = $combat->getId();
        $joueur1Id = $joueur1->getId();

        self::assertNotNull($combatId);
        self::assertNotNull($joueur1Id);

        $this->client->loginUser($joueur1);

        $this->client->request(
            'GET',
            '/combat-en-ligne/'.$combatId,
        );

        self::assertResponseIsSuccessful();

        $etatInitial = $this->lireReponseJson();

        self::assertIsArray($etatInitial['csrf']);
        self::assertIsString($etatInitial['csrf']['plan']);

        $jetonCsrf = $etatInitial['csrf']['plan'];

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
                'HTTP_X_CSRF_TOKEN' => $jetonCsrf,
            ],
        );

        self::assertResponseStatusCodeSame(
            Response::HTTP_CREATED
        );

        $reponse = $this->lireReponseJson();

        self::assertSame(
            'en_attente_adversaire',
            $reponse['etat'],
        );

        self::assertSame(
            $combatId,
            $reponse['combatId'],
        );

        self::assertSame(
            Combat::STATUT_EN_COURS,
            $reponse['statut'],
        );

        self::assertSame(
            1,
            $reponse['numeroRound'],
        );

        self::assertArrayNotHasKey(
            'plan',
            $reponse,
        );

        self::assertArrayNotHasKey(
            'resultats',
            $reponse,
        );

        $combatRecharge = $this->entityManager->find(
            Combat::class,
            $combatId,
        );

        self::assertInstanceOf(
            Combat::class,
            $combatRecharge,
        );

        $planRepository = $this->entityManager
            ->getRepository(PlanRoundCombat::class);

        self::assertInstanceOf(
            PlanRoundCombatRepository::class,
            $planRepository,
        );

        $plans = $planRepository
            ->trouverPourCombatEtRound(
                $combatRecharge,
                1,
            );

        self::assertCount(1, $plans);

        $planEnregistre = $plans[0];

        self::assertSame(
            $joueur1Id,
            $planEnregistre->getJoueur()->getId(),
        );

        self::assertSame(
            'A',
            $planEnregistre->getCibleAttaqueX(),
        );

        self::assertSame(
            'B',
            $planEnregistre->getCibleAttaqueY(),
        );

        self::assertSame(
            'C',
            $planEnregistre->getCibleDefenseX(),
        );

        $this->client->request(
            'GET',
            '/combat-en-ligne/'.$combatId,
        );

        self::assertResponseIsSuccessful();

        $etatEnAttente = $this->lireReponseJson();

        self::assertTrue($etatEnAttente['planSoumis']);
        self::assertFalse($etatEnAttente['adversairePret']);
        self::assertFalse($etatEnAttente['forfaitAutomatique']);
        self::assertSame(
            $planEnregistre
                ->getDateSoumission()
                ->modify('+5 minutes')
                ->format(DATE_ATOM),
            $etatEnAttente['expirationPlan'],
        );

        self::assertSame(
            'D',
            $planEnregistre->getCibleDefenseY(),
        );
    }

    public function testDemarreLeRoundApresDeuxConfirmations(): void
    {
        [
            $combat,
            $joueur1,
            $joueur2,
        ] = $this->creerCombat();

        $combat->initialiserPreparation();
        $this->entityManager->flush();

        $combatId = $combat->getId();

        self::assertNotNull($combatId);

        $this->client->loginUser($joueur1);
        $this->client->request('GET', '/combat-en-ligne/'.$combatId);

        $etatJoueur1 = $this->lireReponseJson();

        self::assertTrue($etatJoueur1['preparation']['active']);
        self::assertFalse($etatJoueur1['preparation']['moiPret']);
        self::assertFalse($etatJoueur1['preparation']['adversairePret']);
        self::assertIsString($etatJoueur1['preparation']['expiration']);

        $this->client->jsonRequest(
            'POST',
            '/combat-en-ligne/'.$combatId.'/pret',
            [],
            [
                'HTTP_X_CSRF_TOKEN' => $etatJoueur1['csrf']['pret'],
            ],
        );

        self::assertResponseIsSuccessful();
        self::assertSame(
            'en_attente_adversaire',
            $this->lireReponseJson()['etat'],
        );

        $this->client->loginUser($joueur2);
        $this->client->request('GET', '/combat-en-ligne/'.$combatId);

        $etatJoueur2 = $this->lireReponseJson();

        self::assertTrue($etatJoueur2['preparation']['active']);
        self::assertFalse($etatJoueur2['preparation']['moiPret']);
        self::assertTrue($etatJoueur2['preparation']['adversairePret']);

        $this->client->jsonRequest(
            'POST',
            '/combat-en-ligne/'.$combatId.'/pret',
            [],
            [
                'HTTP_X_CSRF_TOKEN' => $etatJoueur2['csrf']['pret'],
            ],
        );

        self::assertResponseIsSuccessful();
        self::assertSame(
            'combat_pret',
            $this->lireReponseJson()['etat'],
        );

        $this->client->request('GET', '/combat-en-ligne/'.$combatId);
        $etatFinal = $this->lireReponseJson();

        self::assertFalse($etatFinal['preparation']['active']);
        self::assertTrue($etatFinal['preparation']['moiPret']);
        self::assertTrue($etatFinal['preparation']['adversairePret']);
    }

    public function testAnnuleLaPreparationSiPersonneNestPret(): void
    {
        [
            $combat,
            $joueur1,
        ] = $this->creerCombat();

        $combat->initialiserPreparation();
        $this->entityManager->flush();

        $combatId = $combat->getId();
        self::assertNotNull($combatId);

        $this->vieillirPreparation($combatId);
        $this->client->loginUser($joueur1);
        $this->client->request('GET', '/combat-en-ligne/'.$combatId);
        self::assertResponseIsSuccessful();

        $etat = $this->lireReponseJson();

        self::assertTrue($etat['annulationPreparationAutomatique']);
        self::assertFalse($etat['forfaitPreparationAutomatique']);
        self::assertFalse($etat['forfaitAutomatique']);
        self::assertSame(Combat::STATUT_ANNULE, $etat['statut']);
        self::assertNull($etat['gagnantId']);
        self::assertFalse($etat['preparation']['active']);
        self::assertNull($etat['preparation']['expiration']);
    }

    public function testDeclareLeJoueurPretGagnantApresCinqMinutes(): void
    {
        [
            $combat,
            $joueur1,
        ] = $this->creerCombat();

        $combat->initialiserPreparation();
        $combat->confirmerPret($joueur1);
        $this->entityManager->flush();

        $combatId = $combat->getId();
        $joueur1Id = $joueur1->getId();
        self::assertNotNull($combatId);
        self::assertNotNull($joueur1Id);

        $this->vieillirPreparation($combatId);
        $this->client->loginUser($joueur1);
        $this->client->request('GET', '/combat-en-ligne/'.$combatId);
        self::assertResponseIsSuccessful();

        $etat = $this->lireReponseJson();

        self::assertFalse($etat['annulationPreparationAutomatique']);
        self::assertTrue($etat['forfaitPreparationAutomatique']);
        self::assertTrue($etat['forfaitAutomatique']);
        self::assertSame(Combat::STATUT_FORFAIT, $etat['statut']);
        self::assertSame($joueur1Id, $etat['gagnantId']);
        self::assertFalse($etat['preparation']['active']);
        self::assertNull($etat['preparation']['expiration']);
    }

    public function testRefuseUnDeuxiemePlanDuMemeJoueurPourLeRound(): void
    {
        [
            $combat,
            $joueur1,
        ] = $this->creerCombat();

        $combatId = $combat->getId();
        self::assertNotNull($combatId);

        $this->client->loginUser($joueur1);
        $this->client->request('GET', '/combat-en-ligne/'.$combatId);
        self::assertResponseIsSuccessful();

        $etatInitial = $this->lireReponseJson();
        $plan = [
            'cibleAttaqueX' => 'A',
            'cibleAttaqueY' => 'B',
            'cibleDefenseX' => 'C',
            'cibleDefenseY' => 'D',
        ];
        $entetes = [
            'HTTP_X_CSRF_TOKEN' => $etatInitial['csrf']['plan'],
        ];

        $this->client->jsonRequest(
            'POST',
            '/combat-en-ligne/'.$combatId.'/plan',
            $plan,
            $entetes,
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $this->client->jsonRequest(
            'POST',
            '/combat-en-ligne/'.$combatId.'/plan',
            $plan,
            $entetes,
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $reponse = $this->lireReponseJson();
        self::assertSame(
            'Le joueur a déjà soumis son plan pour ce round.',
            $reponse['erreur'],
        );

        $plans = $this->entityManager
            ->getRepository(PlanRoundCombat::class)
            ->trouverPourCombatEtRound($combat, 1);

        self::assertCount(1, $plans);
    }

    public function testDeclareLeJoueurPretGagnantQuandLeDelaiDuPlanExpire(): void
    {
        [
            $combat,
            $joueur1,
        ] = $this->creerCombat();

        $combatId = $combat->getId();
        $joueur1Id = $joueur1->getId();

        self::assertNotNull($combatId);
        self::assertNotNull($joueur1Id);

        $this->client->loginUser($joueur1);
        $this->client->request('GET', '/combat-en-ligne/'.$combatId);
        self::assertResponseIsSuccessful();

        $etatInitial = $this->lireReponseJson();

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
                'HTTP_X_CSRF_TOKEN' => $etatInitial['csrf']['plan'],
            ],
        );
        self::assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $plans = $this->entityManager
            ->getRepository(PlanRoundCombat::class)
            ->trouverPourCombatEtRound($combat, 1);

        self::assertCount(1, $plans);

        $plan = $plans[0];
        $planId = $plan->getId();

        self::assertNotNull($planId);

        $this->entityManager->getConnection()->executeStatement(
            'UPDATE plan_round_combat'
            .' SET date_soumission = :dateSoumission'
            .' WHERE id = :planId',
            [
                'dateSoumission' => (new \DateTimeImmutable(
                    '-6 minutes'
                ))->format('Y-m-d H:i:s'),
                'planId' => $planId,
            ],
        );
        $this->entityManager->refresh($plan);

        self::assertLessThanOrEqual(
            new \DateTimeImmutable('-5 minutes'),
            $plan->getDateSoumission(),
        );

        $this->client->request(
            'GET',
            '/combat-en-ligne/'.$combatId,
        );
        self::assertResponseIsSuccessful();

        $etatExpire = $this->lireReponseJson();

        self::assertTrue(
            $etatExpire['forfaitAutomatique'],
            json_encode($etatExpire, JSON_THROW_ON_ERROR),
        );
        self::assertSame(Combat::STATUT_FORFAIT, $etatExpire['statut']);
        self::assertSame($joueur1Id, $etatExpire['gagnantId']);
        self::assertNull($etatExpire['expirationPlan']);
    }

    public function testRefuseUnPlanSansJetonCsrfValide(): void
    {
        [
            $combat,
            $joueur1,
        ] = $this->creerCombat();

        $combatId = $combat->getId();

        self::assertNotNull($combatId);

        $this->client->loginUser($joueur1);

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
                'HTTP_X_CSRF_TOKEN' => 'jeton-invalide',
            ],
        );

        self::assertResponseStatusCodeSame(
            Response::HTTP_FORBIDDEN
        );

        $reponse = $this->lireReponseJson();

        self::assertSame(
            'Le jeton CSRF du plan est invalide.',
            $reponse['erreur'],
        );

        $combatRecharge = $this->entityManager->find(
            Combat::class,
            $combatId,
        );

        self::assertInstanceOf(
            Combat::class,
            $combatRecharge,
        );

        $planRepository = $this->entityManager
            ->getRepository(PlanRoundCombat::class);

        self::assertInstanceOf(
            PlanRoundCombatRepository::class,
            $planRepository,
        );

        $plans = $planRepository
            ->trouverPourCombatEtRound(
                $combatRecharge,
                1,
            );

        self::assertCount(0, $plans);
    }

    public function testAbandonneLeCombatAvecUnJetonValide(): void
    {
        [
            $combat,
            $joueur1,
            $joueur2,
        ] = $this->creerCombat();

        $combatId = $combat->getId();
        $joueur2Id = $joueur2->getId();

        self::assertNotNull($combatId);
        self::assertNotNull($joueur2Id);

        $this->client->loginUser($joueur1);

        $this->client->request(
            'GET',
            '/combat-en-ligne/'.$combatId,
        );

        self::assertResponseIsSuccessful();

        $etatInitial = $this->lireReponseJson();

        self::assertIsArray($etatInitial['csrf']);
        self::assertIsString(
            $etatInitial['csrf']['abandon']
        );

        $jetonCsrf = $etatInitial['csrf']['abandon'];

        $this->client->jsonRequest(
            'POST',
            '/combat-en-ligne/'.$combatId.'/abandon',
            [],
            [
                'HTTP_X_CSRF_TOKEN' => $jetonCsrf,
            ],
        );

        self::assertResponseIsSuccessful();

        $reponse = $this->lireReponseJson();

        self::assertSame(
            'combat_abandonne',
            $reponse['etat'],
        );

        self::assertSame(
            Combat::STATUT_ABANDONNE,
            $reponse['statut'],
        );

        self::assertSame(
            1,
            $reponse['numeroRound'],
        );

        self::assertSame(
            $joueur2Id,
            $reponse['gagnantId'],
        );

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
            Combat::STATUT_ABANDONNE,
            $combatRecharge->getStatut(),
        );

        self::assertSame(
            1,
            $combatRecharge->getNumeroRound(),
        );

        self::assertSame(
            $joueur2Id,
            $combatRecharge->getGagnant()?->getId(),
        );
    }

    public function testAnnuleUnCombatEnAttenteAvecUnJetonValide(): void
    {
        [
            $combat,
            $joueur,
        ] = $this->creerCombatEnAttente();

        $combatId = $combat->getId();

        self::assertNotNull($combatId);

        $this->client->loginUser($joueur);

        $this->client->request(
            'GET',
            '/combat-en-ligne/'.$combatId,
        );

        self::assertResponseIsSuccessful();

        $etatInitial = $this->lireReponseJson();

        self::assertIsArray($etatInitial['csrf']);
        self::assertIsString(
            $etatInitial['csrf']['annuler']
        );

        $this->client->jsonRequest(
            'POST',
            '/combat-en-ligne/'.$combatId.'/annuler',
            [],
            [
                'HTTP_X_CSRF_TOKEN' => $etatInitial['csrf']['annuler'],
            ],
        );

        self::assertResponseIsSuccessful();

        $reponse = $this->lireReponseJson();

        self::assertSame('combat_annule', $reponse['etat']);
        self::assertSame(
            Combat::STATUT_ANNULE,
            $reponse['statut'],
        );
        self::assertSame(1, $reponse['numeroRound']);
        self::assertNull($reponse['gagnantId']);

        $this->entityManager->clear();

        $combatRecharge = $this->entityManager->find(
            Combat::class,
            $combatId,
        );

        self::assertInstanceOf(Combat::class, $combatRecharge);
        self::assertTrue($combatRecharge->estAnnule());
        self::assertNull($combatRecharge->getJoueur2());
        self::assertNull($combatRecharge->getGagnant());
    }

    public function testRefuseAnnulationDunCombatEnCours(): void
    {
        [
            $combat,
            $joueur1,
        ] = $this->creerCombat();

        $combatId = $combat->getId();

        self::assertNotNull($combatId);

        $this->client->loginUser($joueur1);
        $this->client->request(
            'GET',
            '/combat-en-ligne/'.$combatId,
        );

        self::assertResponseIsSuccessful();

        $etatInitial = $this->lireReponseJson();

        $this->client->jsonRequest(
            'POST',
            '/combat-en-ligne/'.$combatId.'/annuler',
            [],
            [
                'HTTP_X_CSRF_TOKEN' => $etatInitial['csrf']['annuler'],
            ],
        );

        self::assertResponseStatusCodeSame(Response::HTTP_CONFLICT);

        $reponse = $this->lireReponseJson();

        self::assertSame(
            'Seul un combat en attente peut être annulé.',
            $reponse['erreur'],
        );
    }

    public function testExpireAutomatiquementUnCombatApresCinqMinutes(): void
    {
        [
            $combat,
            $joueur,
        ] = $this->creerCombatEnAttente();

        $combatId = $combat->getId();
        $joueurId = $joueur->getId();

        self::assertNotNull($combatId);
        self::assertNotNull($joueurId);

        $this->entityManager->getConnection()->executeStatement(
            'UPDATE combat '
            .'SET date_creation = DATE_SUB(date_creation, INTERVAL 6 MINUTE) '
            .'WHERE id = ?',
            [$combatId],
        );

        $this->entityManager->clear();

        $joueurRecharge = $this->entityManager->find(
            User::class,
            $joueurId,
        );

        self::assertInstanceOf(User::class, $joueurRecharge);

        $this->client->loginUser($joueurRecharge);
        $this->client->request(
            'GET',
            '/combat-en-ligne/'.$combatId,
        );

        self::assertResponseIsSuccessful();

        $reponse = $this->lireReponseJson();

        self::assertTrue($reponse['expirationAutomatique']);
        self::assertSame(
            Combat::STATUT_ANNULE,
            $reponse['statut'],
        );
        self::assertNull($reponse['gagnantId']);

        $this->entityManager->clear();

        $combatRecharge = $this->entityManager->find(
            Combat::class,
            $combatId,
        );

        self::assertInstanceOf(Combat::class, $combatRecharge);
        self::assertTrue($combatRecharge->estAnnule());
    }

    /**
     * @return array{Combat, User}
     */
    private function creerCombatEnAttente(): array
    {
        $suffixe = bin2hex(random_bytes(6));

        $joueur = (new User())
            ->setEmail(
                'joueur-attente-j41-'.$suffixe.'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        $combat = new Combat($joueur);

        $this->entityManager->persist($joueur);
        $this->entityManager->persist($combat);
        $this->entityManager->flush();

        return [$combat, $joueur];
    }

    /**
     * @return array{Combat, User, User, User}
     */
    private function creerCombat(): array
    {
        $suffixe = bin2hex(random_bytes(6));

        $joueur1 = (new User())
            ->setEmail(
                'joueur1-j38-'.$suffixe.'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        $joueur2 = (new User())
            ->setEmail(
                'joueur2-j38-'.$suffixe.'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        $intrus = (new User())
            ->setEmail(
                'intrus-j38-'.$suffixe.'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        $combat = new Combat($joueur1);
        $combat->setJoueur2($joueur2);
        $combat->setStatut(Combat::STATUT_EN_COURS);

        $this->entityManager->persist($joueur1);
        $this->entityManager->persist($joueur2);
        $this->entityManager->persist($intrus);
        $this->entityManager->flush();

        $stickmenJoueur1 = [];
        $stickmenJoueur2 = [];

        foreach (['A', 'B', 'C', 'D'] as $slot) {
            $stickmenJoueur1[$slot] = $this->creerStickman(
                'J1',
                $slot,
                $suffixe,
                10,
                2,
                1,
            );

            $stickmenJoueur2[$slot] = $this->creerStickman(
                'J2',
                $slot,
                $suffixe,
                12,
                3,
                2,
            );

            $this->entityManager->persist(
                $stickmenJoueur1[$slot]
            );

            $this->entityManager->persist(
                $stickmenJoueur2[$slot]
            );
        }

        $this->entityManager->flush();

        $this->entityManager->persist($combat);

        foreach (['A', 'B', 'C', 'D'] as $slot) {
            $this->entityManager->persist(
                new CombattantCombat(
                    $combat,
                    $joueur1,
                    $slot,
                    $stickmenJoueur1[$slot],
                )
            );

            $this->entityManager->persist(
                new CombattantCombat(
                    $combat,
                    $joueur2,
                    $slot,
                    $stickmenJoueur2[$slot],
                )
            );
        }

        $this->entityManager->flush();

        return [
            $combat,
            $joueur1,
            $joueur2,
            $intrus,
        ];
    }

    private function vieillirPreparation(int $combatId): void
    {
        $this->entityManager->getConnection()->executeStatement(
            'UPDATE combat'
            .' SET date_mise_ajour = :dateMiseAJour'
            .' WHERE id = :combatId',
            [
                'dateMiseAJour' => (new \DateTimeImmutable(
                    '-6 minutes'
                ))->format('Y-m-d H:i:s'),
                'combatId' => $combatId,
            ],
        );

        $combat = $this->entityManager->find(Combat::class, $combatId);
        self::assertInstanceOf(Combat::class, $combat);
        $this->entityManager->refresh($combat);
    }

    private function creerStickman(
        string $joueur,
        string $slot,
        string $suffixe,
        int $pv,
        int $attaque,
        int $defense,
    ): Stickman {
        return (new Stickman())
            ->setNom(
                sprintf('Stickman %s %s', $joueur, $slot)
            )
            ->setSlug(
                sprintf(
                    'stickman-%s-%s-%s',
                    strtolower($joueur),
                    strtolower($slot),
                    $suffixe,
                )
            )
            ->setDescription(
                sprintf(
                    'Stickman de test %s %s.',
                    $joueur,
                    $slot,
                )
            )
            ->setImage(
                sprintf(
                    'stickman-%s-%s.png',
                    strtolower($joueur),
                    strtolower($slot),
                )
            )
            ->setRarete(1)
            ->setPv($pv)
            ->setAttaque($attaque)
            ->setDefense($defense)
            ->setStatutActif(true);
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
