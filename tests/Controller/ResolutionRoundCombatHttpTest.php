<?php

namespace App\Tests\Controller;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\ResultatRoundCombat;
use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\CombattantCombatRepository;
use App\Repository\ResultatRoundCombatRepository;
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

    public function testDeuxParticipantsResolventPlusieursRoundsViaHttp(): void
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

        self::assertNull(
            $etatJoueur1['dernierRound']
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

        self::assertNull(
            $etatJoueur2['dernierRound']
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

        self::assertSame(
            1,
            $combatRecharge->getDernierRoundResolu(),
        );

        self::assertEquals(
            $resolution['resultats'],
            $combatRecharge->getDerniersResultats(),
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

        /*
         * Le joueur 1, qui n’a pas déclenché la résolution,
         * reçoit maintenant exactement le même résultat finalisé.
         */
        $this->client->loginUser($joueur1);

        $this->client->request(
            'GET',
            '/combat-en-ligne/'.$combatId,
        );

        self::assertResponseIsSuccessful();

        $etatFinalJoueur1 = $this->lireReponseJson();

        self::assertSame(
            1,
            $etatFinalJoueur1['dernierRound']['numero'],
        );

        self::assertSame(
            'joueur1',
            $etatFinalJoueur1['dernierRound']['positionMoi'],
        );

        self::assertEquals(
            $resolution['resultats'],
            $etatFinalJoueur1['dernierRound']['resultats'],
        );

        self::assertArrayNotHasKey(
            'plans',
            $etatFinalJoueur1,
        );

        $etatFinalJson = json_encode(
            $etatFinalJoueur1,
            JSON_THROW_ON_ERROR,
        );

        self::assertStringNotContainsString(
            'cibleAttaqueX',
            $etatFinalJson,
        );

        self::assertStringNotContainsString(
            'cibleDefenseX',
            $etatFinalJson,
        );

        /*
         * Le second participant retrouve aussi ce résultat via GET,
         * indépendamment de la réponse de son POST.
         */
        $this->client->loginUser($joueur2);

        $this->client->request(
            'GET',
            '/combat-en-ligne/'.$combatId,
        );

        self::assertResponseIsSuccessful();

        $etatFinalJoueur2 = $this->lireReponseJson();

        self::assertSame(
            1,
            $etatFinalJoueur2['dernierRound']['numero'],
        );

        self::assertSame(
            'joueur2',
            $etatFinalJoueur2['dernierRound']['positionMoi'],
        );

        self::assertEquals(
            $etatFinalJoueur1['dernierRound']['resultats'],
            $etatFinalJoueur2['dernierRound']['resultats'],
        );

        /*
         * Les deux participants soumettent ensuite leur plan du round 2.
         * Les mêmes identités de session sont conservées afin que le
         * navigateur de test ne sérialise pas deux objets User différents
         * pour un même identifiant après le clear() précédent.
         */
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
                'HTTP_X_CSRF_TOKEN' =>
                    $etatFinalJoueur1['csrf']['plan'],
            ],
        );

        self::assertResponseStatusCodeSame(
            Response::HTTP_CREATED
        );

        $attenteRound2 = $this->lireReponseJson();

        self::assertSame(
            'en_attente_adversaire',
            $attenteRound2['etat'],
        );

        self::assertSame(
            2,
            $attenteRound2['numeroRound'],
        );

        $this->client->loginUser($joueur2);

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
                    $etatFinalJoueur2['csrf']['plan'],
            ],
        );

        self::assertResponseStatusCodeSame(
            Response::HTTP_OK
        );

        $resolutionRound2 = $this->lireReponseJson();

        self::assertSame(
            'round_resolu',
            $resolutionRound2['etat'],
        );

        self::assertSame(
            3,
            $resolutionRound2['numeroRound'],
        );

        self::assertSame(
            6,
            $resolutionRound2['resultats']['joueur1_A']['pvRestants'],
        );

        self::assertSame(
            6,
            $resolutionRound2['resultats']['joueur2_A']['pvRestants'],
        );

        /*
         * L’état HTTP expose les deux résultats déjà résolus dans l’ordre,
         * sans jamais inclure les choix secrets des plans.
         */
        $this->client->request(
            'GET',
            '/combat-en-ligne/'.$combatId,
        );

        self::assertResponseIsSuccessful();

        $etatApresDeuxRounds = $this->lireReponseJson();

        self::assertCount(
            2,
            $etatApresDeuxRounds['historiqueRounds'],
        );

        self::assertSame(
            [1, 2],
            array_column(
                $etatApresDeuxRounds['historiqueRounds'],
                'numero',
            ),
        );

        self::assertEquals(
            $resolution['resultats'],
            $etatApresDeuxRounds['historiqueRounds'][0]['resultats'],
        );

        self::assertEquals(
            $resolutionRound2['resultats'],
            $etatApresDeuxRounds['historiqueRounds'][1]['resultats'],
        );

        $etatApresDeuxRoundsJson = json_encode(
            $etatApresDeuxRounds,
            JSON_THROW_ON_ERROR,
        );

        self::assertStringNotContainsString(
            'cibleAttaqueX',
            $etatApresDeuxRoundsJson,
        );

        self::assertStringNotContainsString(
            'cibleDefenseX',
            $etatApresDeuxRoundsJson,
        );

        /*
         * Nouvelle relecture MySQL : les deux résultats doivent exister,
         * rester ordonnés et conserver leurs valeurs propres.
         */
        $this->entityManager->clear();

        $combatDeuxRounds = $this->entityManager->find(
            Combat::class,
            $combatId,
        );

        self::assertInstanceOf(
            Combat::class,
            $combatDeuxRounds,
        );

        $resultatRoundRepository =
            $this->entityManager->getRepository(
                ResultatRoundCombat::class
            );

        self::assertInstanceOf(
            ResultatRoundCombatRepository::class,
            $resultatRoundRepository,
        );

        $historique = $resultatRoundRepository
            ->trouverPourCombat($combatDeuxRounds);

        self::assertCount(2, $historique);

        self::assertSame(
            [1, 2],
            array_map(
                static fn (
                    ResultatRoundCombat $resultat
                ): int => $resultat->getNumeroRound(),
                $historique,
            ),
        );

        self::assertEquals(
            $resolution['resultats'],
            $historique[0]->getResultats(),
        );

        self::assertEquals(
            $resolutionRound2['resultats'],
            $historique[1]->getResultats(),
        );

        self::assertSame(
            8,
            $historique[0]
                ->getResultats()['joueur1_A']['pvRestants'],
        );

        self::assertSame(
            6,
            $historique[1]
                ->getResultats()['joueur1_A']['pvRestants'],
        );
    }

    public function testDeuxParticipantsAtteignentLaFinEtConsultentLeRapport(): void
    {
        [
            $combat,
            $joueur1,
            $joueur2,
        ] = $this->creerCombatAvecSnapshots(1);

        $combatId = $combat->getId();
        self::assertNotNull($combatId);

        $round1 = [
            'cibleAttaqueX' => 'A',
            'cibleAttaqueY' => 'D',
            'cibleDefenseX' => 'A',
            'cibleDefenseY' => 'D',
        ];
        $round2 = [
            'cibleAttaqueX' => 'B',
            'cibleAttaqueY' => 'C',
            'cibleDefenseX' => 'B',
            'cibleDefenseY' => 'C',
        ];

        $this->soumettrePlan(
            $joueur1,
            $combatId,
            $round1,
            Response::HTTP_CREATED,
        );
        $resolutionRound1 = $this->soumettrePlan(
            $joueur2,
            $combatId,
            $round1,
            Response::HTTP_OK,
        );

        self::assertSame('round_resolu', $resolutionRound1['etat']);
        self::assertSame(Combat::STATUT_EN_COURS, $resolutionRound1['statut']);
        self::assertSame(2, $resolutionRound1['numeroRound']);

        $this->soumettrePlan(
            $joueur1,
            $combatId,
            $round2,
            Response::HTTP_CREATED,
        );
        $resolutionFinale = $this->soumettrePlan(
            $joueur2,
            $combatId,
            $round2,
            Response::HTTP_OK,
        );

        self::assertSame('combat_termine', $resolutionFinale['etat']);
        self::assertSame(Combat::STATUT_TERMINE, $resolutionFinale['statut']);
        self::assertSame(2, $resolutionFinale['numeroRound']);
        self::assertNull($resolutionFinale['gagnantId']);

        $this->client->loginUser($joueur1);
        $this->client->request('GET', '/combat-en-ligne/'.$combatId);
        self::assertResponseIsSuccessful();

        $etatFinal = $this->lireReponseJson();
        self::assertSame(Combat::STATUT_TERMINE, $etatFinal['statut']);
        self::assertCount(2, $etatFinal['historiqueRounds']);
        self::assertSame(
            [1, 2],
            array_column($etatFinal['historiqueRounds'], 'numero'),
        );

        foreach ($etatFinal['moi']['combattants'] as $combattant) {
            self::assertSame(0, $combattant['pvActuels']);
            self::assertFalse($combattant['vivant']);
        }

        $this->client->request('GET', '/combats/'.$combatId.'/rapport');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.rapport-resultat', 'Match nul');
        self::assertSelectorCount(2, '.rapport-round');

        $this->client->loginUser($joueur2);
        $this->client->request('GET', '/combats/'.$combatId.'/rapport');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.rapport-resultat', 'Match nul');

        $this->client->request('GET', '/salon-combat-en-ligne');
        self::assertResponseIsSuccessful();

        $salon = $this->lireReponseJson();
        self::assertNull($salon['combatActifId']);
        self::assertSame($combatId, $salon['historiqueCombats'][0]['id']);
        self::assertSame('egalite', $salon['historiqueCombats'][0]['resultat']);
    }

    public function testTroisRoundsSansDegatTerminentLeCombatEnMatchNul(): void
    {
        [
            $combat,
            $joueur1,
            $joueur2,
        ] = $this->creerCombatAvecSnapshots(
            pv: 10,
            attaque: 1,
            defense: 10,
        );

        $combatId = $combat->getId();
        self::assertNotNull($combatId);

        $plan = [
            'cibleAttaqueX' => 'A',
            'cibleAttaqueY' => 'B',
            'cibleDefenseX' => 'A',
            'cibleDefenseY' => 'B',
        ];

        for ($numeroRound = 1; $numeroRound <= 3; $numeroRound++) {
            $this->soumettrePlan(
                $joueur1,
                $combatId,
                $plan,
                Response::HTTP_CREATED,
            );

            $resolution = $this->soumettrePlan(
                $joueur2,
                $combatId,
                $plan,
                Response::HTTP_OK,
            );

            if ($numeroRound < 3) {
                self::assertSame('round_resolu', $resolution['etat']);
                self::assertSame(
                    Combat::STATUT_EN_COURS,
                    $resolution['statut'],
                );
                self::assertSame(
                    $numeroRound + 1,
                    $resolution['numeroRound'],
                );

                continue;
            }

            self::assertSame('combat_termine', $resolution['etat']);
            self::assertSame(
                Combat::STATUT_TERMINE,
                $resolution['statut'],
            );
            self::assertSame(3, $resolution['numeroRound']);
            self::assertNull($resolution['gagnantId']);
        }

        $this->client->loginUser($joueur1);
        $this->client->request('GET', '/combat-en-ligne/'.$combatId);
        self::assertResponseIsSuccessful();

        $etatFinal = $this->lireReponseJson();
        self::assertCount(3, $etatFinal['historiqueRounds']);

        foreach ($etatFinal['historiqueRounds'] as $round) {
            foreach ($round['resultats'] as $resultat) {
                self::assertSame(0, $resultat['degatsEffectifs']);
            }
        }

        $this->client->request('GET', '/combats/'.$combatId.'/rapport');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.rapport-resultat', 'Match nul');
        self::assertSelectorCount(3, '.rapport-round');
    }

    /**
     * @return array{Combat, User, User}
     */
    private function creerCombatAvecSnapshots(
        int $pv = 10,
        int $attaque = 1,
        int $defense = 0,
    ): array
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
                $pv,
                $attaque,
                $defense,
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
        int $pv,
        int $attaque,
        int $defense,
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
            ->setPv($pv)
            ->setAttaque($attaque)
            ->setDefense($defense)
            ->setStatutActif(true);
    }

    /**
     * @param array<string, string> $plan
     *
     * @return array<string, mixed>
     */
    private function soumettrePlan(
        User $joueur,
        int $combatId,
        array $plan,
        int $statutAttendu,
    ): array {
        $this->client->loginUser($joueur);
        $this->client->request('GET', '/combat-en-ligne/'.$combatId);
        self::assertResponseIsSuccessful();

        $etat = $this->lireReponseJson();

        $this->client->jsonRequest(
            'POST',
            '/combat-en-ligne/'.$combatId.'/plan',
            $plan,
            [
                'HTTP_X_CSRF_TOKEN' => $etat['csrf']['plan'],
            ],
        );

        self::assertResponseStatusCodeSame($statutAttendu);

        return $this->lireReponseJson();
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
