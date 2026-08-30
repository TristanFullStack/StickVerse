<?php

namespace App\Tests\Controller;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Repository\CombattantCombatRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class SalonCombatEnLigneControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private CombatRepository $combatRepository;
    private CombattantCombatRepository $combattantRepository;

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

        $combatRepository = $entityManager->getRepository(
            Combat::class
        );

        $combattantRepository = $entityManager->getRepository(
            CombattantCombat::class
        );

        self::assertInstanceOf(
            CombatRepository::class,
            $combatRepository,
        );

        self::assertInstanceOf(
            CombattantCombatRepository::class,
            $combattantRepository,
        );

        $this->entityManager = $entityManager;
        $this->combatRepository = $combatRepository;
        $this->combattantRepository =
            $combattantRepository;

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

    public function testCreePuisRejointUnCombatPriveParCode(): void
    {
        [
            $joueur1,
            $joueur2,
            $equipeJoueur1,
            $equipeJoueur2,
        ] = $this->creerDonneesDuSalon();

        $joueur1Id = $joueur1->getId();
        $joueur2Id = $joueur2->getId();
        $equipeJoueur1Id = $equipeJoueur1->getId();
        $equipeJoueur2Id = $equipeJoueur2->getId();

        self::assertNotNull($joueur1Id);
        self::assertNotNull($joueur2Id);
        self::assertNotNull($equipeJoueur1Id);
        self::assertNotNull($equipeJoueur2Id);

        /*
         * Le joueur 1 consulte le salon.
         */
        $this->client->loginUser($joueur1);

        $this->client->request(
            'GET',
            '/salon-combat-en-ligne',
        );

        self::assertResponseIsSuccessful();

        $salonJoueur1 = $this->lireReponseJson();

        self::assertNull(
            $salonJoueur1['combatActifId']
        );

        self::assertCount(
            1,
            $salonJoueur1['equipes'],
        );

        self::assertSame(
            $equipeJoueur1Id,
            $salonJoueur1['equipes'][0]['id'],
        );

        self::assertSame(
            'Équipe salon joueur 1',
            $salonJoueur1['equipes'][0]['nom'],
        );

        self::assertCount(
            4,
            $salonJoueur1['equipes'][0]['combattants'],
        );

        self::assertSame(
            ['A', 'B', 'C', 'D'],
            array_column(
                $salonJoueur1['equipes'][0]['combattants'],
                'slot',
            ),
        );

        self::assertSame(
            'Stickman salon J1-A',
            $salonJoueur1['equipes'][0]['combattants'][0]['nom'],
        );

        self::assertSame(
            'stickman-salon-j39-j1-a.png',
            $salonJoueur1['equipes'][0]['combattants'][0]['image'],
        );

        self::assertSame(
            1,
            $salonJoueur1['equipes'][0]['combattants'][0]['rarete'],
        );

        self::assertSame(
            10,
            $salonJoueur1['equipes'][0]['combattants'][0]['pv'],
        );

        self::assertSame(
            2,
            $salonJoueur1['equipes'][0]['combattants'][0]['attaque'],
        );

        self::assertSame(
            1,
            $salonJoueur1['equipes'][0]['combattants'][0]['defense'],
        );

        self::assertIsInt(
            $salonJoueur1['equipes'][0]['combattants'][0]['stickmanId'],
        );

        self::assertCount(
            0,
            $salonJoueur1['combatsDisponibles'],
        );

        self::assertSame(
            [],
            $salonJoueur1['historiqueCombats'],
        );

        self::assertIsArray(
            $salonJoueur1['csrf']
        );

        self::assertIsString(
            $salonJoueur1['csrf']['creer']
        );

        /*
         * Le joueur 1 crée un combat avec son équipe.
         */
        $this->client->jsonRequest(
            'POST',
            '/salon-combat-en-ligne/creer',
            [
                'equipeId' => $equipeJoueur1Id,
                'prive' => true,
            ],
            [
                'HTTP_X_CSRF_TOKEN' =>
                    $salonJoueur1['csrf']['creer'],
            ],
        );

        self::assertResponseStatusCodeSame(
            Response::HTTP_CREATED
        );

        $creation = $this->lireReponseJson();

        self::assertSame(
            'combat_cree',
            $creation['etat'],
        );

        self::assertSame(
            Combat::STATUT_EN_ATTENTE,
            $creation['statut'],
        );

        self::assertSame(
            1,
            $creation['numeroRound'],
        );

        self::assertIsInt(
            $creation['combatId']
        );

        self::assertMatchesRegularExpression(
            '/^SV-[A-F0-9]{6}$/',
            $creation['codeInvitation'],
        );

        self::assertTrue($creation['prive']);

        $combatId = $creation['combatId'];
        $codeInvitation = $creation['codeInvitation'];

        $combatCree = $this->combatRepository->find(
            $combatId
        );

        self::assertInstanceOf(
            Combat::class,
            $combatCree,
        );

        self::assertSame(
            $joueur1Id,
            $combatCree->getJoueur1()->getId(),
        );

        self::assertNull(
            $combatCree->getJoueur2()
        );

        self::assertSame(
            $codeInvitation,
            $combatCree->getCodeInvitation(),
        );

        self::assertTrue($combatCree->estPrive());

        self::assertCount(
            4,
            $combatCree->getCombattants(),
        );

        $this->client->request(
            'GET',
            '/combat-en-ligne/'.$combatId,
        );
        self::assertResponseIsSuccessful();

        $etatCombatCree = $this->lireReponseJson();

        self::assertSame(
            $codeInvitation,
            $etatCombatCree['codeInvitation'],
        );

        self::assertTrue($etatCombatCree['prive']);

        /*
         * Le joueur 2 consulte ensuite le salon.
         */
        $this->client->loginUser($joueur2);

        $this->client->request(
            'GET',
            '/salon-combat-en-ligne',
        );

        self::assertResponseIsSuccessful();

        $salonJoueur2 = $this->lireReponseJson();

        self::assertNull(
            $salonJoueur2['combatActifId']
        );

        self::assertCount(
            1,
            $salonJoueur2['equipes'],
        );

        self::assertSame(
            $equipeJoueur2Id,
            $salonJoueur2['equipes'][0]['id'],
        );

        self::assertCount(
            0,
            $salonJoueur2['combatsDisponibles'],
        );

        self::assertIsArray(
            $salonJoueur2['csrf']
        );

        self::assertIsString(
            $salonJoueur2['csrf']['rejoindre']
        );

        /*
         * Le joueur 2 ne peut pas contourner le code en utilisant
         * directement l’identifiant du combat privé.
         */
        $this->client->jsonRequest(
            'POST',
            '/salon-combat-en-ligne/'.$combatId.'/rejoindre',
            [
                'equipeId' => $equipeJoueur2Id,
            ],
            [
                'HTTP_X_CSRF_TOKEN' =>
                    $salonJoueur2['csrf']['rejoindre'],
            ],
        );

        self::assertResponseStatusCodeSame(
            Response::HTTP_CONFLICT
        );

        self::assertSame(
            'Ce combat privé doit être rejoint avec son code d’invitation.',
            $this->lireReponseJson()['erreur'],
        );

        /*
         * Le joueur 2 rejoint finalement le combat avec son code.
         */
        $this->client->jsonRequest(
            'POST',
            '/salon-combat-en-ligne/rejoindre-par-code',
            [
                'equipeId' => $equipeJoueur2Id,
                'code' => strtolower($codeInvitation),
            ],
            [
                'HTTP_X_CSRF_TOKEN' =>
                    $salonJoueur2['csrf']['rejoindre'],
            ],
        );

        self::assertResponseStatusCodeSame(
            Response::HTTP_OK
        );

        $jonction = $this->lireReponseJson();

        self::assertSame(
            'combat_rejoint',
            $jonction['etat'],
        );

        self::assertSame(
            $combatId,
            $jonction['combatId'],
        );

        self::assertSame(
            Combat::STATUT_EN_COURS,
            $jonction['statut'],
        );

        self::assertSame(
            1,
            $jonction['numeroRound'],
        );

        /*
         * On vide Doctrine afin de relire l’état final
         * directement depuis MySQL.
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

        self::assertTrue($combatEnCours->estEnPreparation());
        self::assertFalse($combatEnCours->estPretAJouer());

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

        /*
         * Après la jonction, le combat n’est plus disponible
         * dans le salon.
         */
        $combatsEncoreDisponibles =
            $this->combatRepository
                ->trouverDisponiblesPour(
                    $joueur2Final
                );

        self::assertCount(
            0,
            $combatsEncoreDisponibles,
        );

        /*
         * Le joueur 2 voit maintenant son combat actif.
         */
        $this->client->loginUser($joueur2Final);

        $this->client->request(
            'GET',
            '/salon-combat-en-ligne',
        );

        self::assertResponseIsSuccessful();

        $salonApresJonction =
            $this->lireReponseJson();

        self::assertSame(
            $combatId,
            $salonApresJonction['combatActifId'],
        );

        self::assertCount(
            0,
            $salonApresJonction
                ['combatsDisponibles'],
        );
    }

    public function testExposeLesPublicsMaisCacheLesPrivesDuSalon(): void
    {
        [
            $joueur1,
            $joueur2,
        ] = $this->creerDonneesDuSalon();

        $combatPublic = new Combat($joueur1);
        $combatPrive = (new Combat($joueur1))
            ->setPrive(true);

        $this->entityManager->persist($combatPublic);
        $this->entityManager->persist($combatPrive);
        $this->entityManager->flush();

        $combatPublicId = $combatPublic->getId();

        self::assertNotNull($combatPublicId);

        $this->client->loginUser($joueur2);
        $this->client->request('GET', '/salon-combat-en-ligne');

        self::assertResponseIsSuccessful();

        $combatsDisponibles = $this->lireReponseJson()
            ['combatsDisponibles'];

        self::assertCount(1, $combatsDisponibles);
        self::assertSame(
            $combatPublicId,
            $combatsDisponibles[0]['id'],
        );
        self::assertFalse($combatsDisponibles[0]['prive']);
    }

    public function testExposeUniquementHistoriqueJoueDuJoueur(): void
    {
        $suffixe = bin2hex(random_bytes(6));
        $joueur = (new User())
            ->setEmail('historique-'.$suffixe.'@example.com')
            ->setPassword('mot-de-passe-test');
        $adversaire = (new User())
            ->setEmail('adversaire-'.$suffixe.'@example.com')
            ->setPassword('mot-de-passe-test');
        $exterieur = (new User())
            ->setEmail('exterieur-'.$suffixe.'@example.com')
            ->setPassword('mot-de-passe-test');

        $this->entityManager->persist($joueur);
        $this->entityManager->persist($adversaire);
        $this->entityManager->persist($exterieur);

        $victoire = (new Combat($joueur))
            ->setJoueur2($adversaire)
            ->setStatut(Combat::STATUT_TERMINE)
            ->setGagnant($joueur)
            ->enregistrerResultatsRound(
                3,
                ['joueur2_A' => ['degatsEffectifs' => 4]],
            );

        $abandon = (new Combat($adversaire))
            ->setJoueur2($joueur)
            ->setStatut(Combat::STATUT_ABANDONNE)
            ->setGagnant($adversaire)
            ->enregistrerResultatsRound(
                1,
                ['joueur1_A' => ['degatsEffectifs' => 2]],
            );

        $annule = (new Combat($joueur))
            ->setStatut(Combat::STATUT_ANNULE);

        $combatExterieur = (new Combat($adversaire))
            ->setJoueur2($exterieur)
            ->setStatut(Combat::STATUT_TERMINE)
            ->setGagnant($exterieur)
            ->enregistrerResultatsRound(
                2,
                ['joueur1_A' => ['degatsEffectifs' => 3]],
            );

        $this->entityManager->persist($victoire);
        $this->entityManager->persist($abandon);
        $this->entityManager->persist($annule);
        $this->entityManager->persist($combatExterieur);
        $this->entityManager->flush();

        $victoireId = $victoire->getId();
        $abandonId = $abandon->getId();

        self::assertNotNull($victoireId);
        self::assertNotNull($abandonId);

        $this->client->loginUser($joueur);
        $this->client->request('GET', '/salon-combat-en-ligne');

        self::assertResponseIsSuccessful();

        $salon = $this->lireReponseJson();
        $historique = $salon['historiqueCombats'];

        self::assertIsArray($historique);
        self::assertCount(2, $historique);

        self::assertSame($abandonId, $historique[0]['id']);
        self::assertSame(
            $adversaire->getEmail(),
            $historique[0]['adversaireEmail'],
        );
        self::assertSame(
            Combat::STATUT_ABANDONNE,
            $historique[0]['statut'],
        );
        self::assertSame('abandon', $historique[0]['resultat']);
        self::assertSame(1, $historique[0]['nombreRounds']);
        self::assertIsString($historique[0]['dateFin']);

        self::assertSame($victoireId, $historique[1]['id']);
        self::assertSame(
            $adversaire->getEmail(),
            $historique[1]['adversaireEmail'],
        );
        self::assertSame(
            Combat::STATUT_TERMINE,
            $historique[1]['statut'],
        );
        self::assertSame('victoire', $historique[1]['resultat']);
        self::assertSame(3, $historique[1]['nombreRounds']);
        self::assertIsString($historique[1]['dateFin']);
    }

    /**
     * @return array{User, User, Equipe, Equipe}
     */
    private function creerDonneesDuSalon(): array
    {
        $suffixe = bin2hex(random_bytes(6));

        $joueur1 = (new User())
            ->setEmail(
                'joueur1-salon-j39-'
                .$suffixe
                .'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        $joueur2 = (new User())
            ->setEmail(
                'joueur2-salon-j39-'
                .$suffixe
                .'@example.com'
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

        $this->entityManager->flush();

        $equipeJoueur1 = $this->creerEquipe(
            'Équipe salon joueur 1',
            $joueur1,
            $stickmenJoueur1,
        );

        $equipeJoueur2 = $this->creerEquipe(
            'Équipe salon joueur 2',
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

        return [
            $joueur1,
            $joueur2,
            $equipeJoueur1,
            $equipeJoueur2,
        ];
    }

    private function creerStickman(
        string $nomCourt,
        string $suffixe,
    ): Stickman {
        $slug = strtolower($nomCourt);

        return (new Stickman())
            ->setNom(
                'Stickman salon '.$nomCourt
            )
            ->setSlug(
                'stickman-salon-j39-'
                .$slug
                .'-'
                .$suffixe
            )
            ->setDescription(
                'Stickman utilisé par le test HTTP du salon.'
            )
            ->setImage(
                'stickman-salon-j39-'
                .$slug
                .'.png'
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
