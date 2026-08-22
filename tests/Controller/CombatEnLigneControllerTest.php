<?php

namespace App\Tests\Controller;

use App\Entity\Combat;
use App\Entity\PlanRoundCombat;
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
        self::assertFalse($donnees['planSoumis']);
        self::assertFalse($donnees['adversairePret']);

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

        self::assertIsString(
            $donnees['csrf']['plan']
        );

        self::assertIsString(
            $donnees['csrf']['abandon']
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

        self::assertSame(
            'D',
            $planEnregistre->getCibleDefenseY(),
        );
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
        $this->entityManager->persist($combat);
        $this->entityManager->flush();

        return [
            $combat,
            $joueur1,
            $joueur2,
            $intrus,
        ];
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