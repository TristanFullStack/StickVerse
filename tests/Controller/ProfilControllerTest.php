<?php

namespace App\Tests\Controller;

use App\Entity\Combat;
use App\Entity\ClassementSaisonJoueur;
use App\Entity\CollectionJeu;
use App\Entity\MouvementPieces;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProfilControllerTest extends WebTestCase
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

        if (!is_string($nomBase) || !str_ends_with($nomBase, '_test')) {
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

    public function testRedirigeLeVisiteurVersLaConnexion(): void
    {
        $this->client->request('GET', '/profil');

        self::assertResponseRedirects('/login');
    }

    public function testAfficheLeProfilEtLesStatistiquesDuJoueur(): void
    {
        $suffixe = bin2hex(random_bytes(6));
        $joueur = (new User())
            ->setEmail('profil-'.$suffixe.'@example.com')
            ->setPassword('mot-de-passe-test');
        $adversaire = (new User())
            ->setEmail('adversaire-'.$suffixe.'@example.com')
            ->setPassword('mot-de-passe-test');

        $this->entityManager->persist($joueur);
        $this->entityManager->persist($adversaire);

        $combats = [
            (new Combat($joueur))
                ->setJoueur2($adversaire)
                ->setStatut(Combat::STATUT_TERMINE)
                ->setGagnant($joueur),
            (new Combat($adversaire))
                ->setJoueur2($joueur)
                ->setStatut(Combat::STATUT_FORFAIT)
                ->setGagnant($joueur),
            (new Combat($joueur))
                ->setJoueur2($adversaire)
                ->setStatut(Combat::STATUT_ABANDONNE)
                ->setGagnant($adversaire),
            (new Combat($joueur))
                ->setJoueur2($adversaire)
                ->setStatut(Combat::STATUT_TERMINE),
        ];

        foreach ($combats as $combat) {
            $this->entityManager->persist($combat);
        }

        $this->entityManager->persist(new MouvementPieces(
            $joueur,
            -120,
            MouvementPieces::TYPE_ACHAT_CAISSE,
            'Ouverture de la caisse Origine',
        ));

        $this->entityManager->flush();

        $this->client->loginUser($joueur);
        $this->client->request('GET', '/profil');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Mon profil');
        self::assertSelectorTextContains(
            '.site-content',
            $joueur->getUserIdentifier(),
        );
        self::assertSelectorTextContains(
            '[data-profile-pseudo]',
            $joueur->getPseudo(),
        );
        self::assertSelectorTextContains(
            '[data-profile-pieces]',
            '1000 pièces',
        );
        self::assertSelectorNotExists('[data-profile-mouvements]');
        self::assertSelectorNotExists('[data-profile-recompenses]');
        self::assertSelectorExists('.site-navigation a[href="/recompenses"]');
        self::assertSelectorExists('a[href="/profil/pseudo"]');
        self::assertSelectorTextContains(
            '[data-profile-account-type]',
            'Joueur',
        );
        self::assertSelectorTextContains(
            '[data-profile-stat="total"]',
            '4',
        );
        self::assertSelectorTextContains(
            '[data-profile-stat="victoires"]',
            '2',
        );
        self::assertSelectorTextContains(
            '[data-profile-stat="defaites"]',
            '1',
        );
        self::assertSelectorTextContains(
            '[data-profile-stat="matchs-nuls"]',
            '1',
        );
        self::assertSelectorExists(
            '.site-account a[href="/profil"][aria-current="page"]',
        );
    }

    public function testIdentifieLeCompteAdministrateur(): void
    {
        $administrateur = (new User())
            ->setEmail('profil-admin@example.com')
            ->setPassword('mot-de-passe-test')
            ->setRoles(['ROLE_ADMIN']);

        $this->entityManager->persist($administrateur);
        $this->entityManager->flush();

        $this->client->loginUser($administrateur);
        $this->client->request('GET', '/profil');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            '[data-profile-account-type]',
            'Administrateur',
        );
        self::assertSelectorExists('[data-navigation-admin]');
    }

    public function testAfficheLesTropheesSaisonniersDuJoueur(): void
    {
        $joueur = (new User())
            ->setEmail('profil-trophee@example.com')
            ->setPassword('mot-de-passe-test');
        $saison = (new CollectionJeu())
            ->setNom('Saison trophée')
            ->setSlug('saison-trophee-'.bin2hex(random_bytes(3)))
            ->setDescription('Saison utilisée pour le trophée du profil.')
            ->setSaison(1)
            ->setDateFin(new DateTimeImmutable('2026-08-31 23:59:59'));
        $classement = (new ClassementSaisonJoueur($joueur, $saison))
            ->enregistrerResultat(200, 1.0);

        $this->entityManager->persist($joueur);
        $this->entityManager->persist($saison);
        $this->entityManager->persist($classement);
        $this->entityManager->flush();

        $this->client->loginUser($joueur);
        $this->client->request('GET', '/profil');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-profile-trophees]', 'Saison 1');
        self::assertSelectorTextContains('[data-profile-trophees]', 'Sentinelle');
        self::assertSelectorTextContains('[data-profile-trophees]', '5000 pièces');
        self::assertSelectorTextContains('[data-profile-trophees]', 'Saison terminée');
    }

    public function testReclameLaRecompenseQuotidienne(): void
    {
        $joueur = (new User())
            ->setEmail('profil-quotidien@example.com')
            ->setPassword('mot-de-passe-test');

        $this->entityManager->persist($joueur);
        $this->entityManager->flush();

        $this->client->loginUser($joueur);
        $crawler = $this->client->request('GET', '/recompenses');
        $form = $crawler->filter('[data-profile-recompense-quotidienne]')->form();

        $this->client->submit($form);

        self::assertResponseRedirects('/recompenses');
        $this->client->followRedirect();
        self::assertSelectorTextContains('[data-solde-pieces]', '2000 pièces');
        self::assertSelectorTextContains(
            '.alert-success',
            'Récompense quotidienne récupérée',
        );
    }

    public function testNaffichePasDeuxFoisLaRecompenseLeMemeJour(): void
    {
        $joueur = (new User())
            ->setEmail('profil-quotidien-deja@example.com')
            ->setPassword('mot-de-passe-test')
            ->setDateDerniereRecompenseQuotidienne(new DateTimeImmutable());

        $this->entityManager->persist($joueur);
        $this->entityManager->flush();

        $this->client->loginUser($joueur);
        $this->client->request('GET', '/recompenses');

        self::assertSelectorNotExists('[data-profile-recompense-quotidienne]');
        self::assertSelectorTextContains(
            '[data-profile-recompense-indisponible]',
            'Récompense déjà récupérée aujourd’hui',
        );
    }

    public function testReclameUnObjectifDisponible(): void
    {
        $joueur = (new User())
            ->setEmail('profil-objectif@example.com')
            ->setPassword('mot-de-passe-test');
        $adversaire = (new User())
            ->setEmail('profil-objectif-adversaire@example.com')
            ->setPassword('mot-de-passe-test');
        $combat = (new Combat($joueur))
            ->setJoueur2($adversaire)
            ->setStatut(Combat::STATUT_TERMINE)
            ->setGagnant($joueur);

        $this->entityManager->persist($joueur);
        $this->entityManager->persist($adversaire);
        $this->entityManager->persist($combat);
        $this->entityManager->flush();

        $this->client->loginUser($joueur);
        $crawler = $this->client->request('GET', '/recompenses');
        $form = $crawler
            ->filter('[data-profile-objectif="premier_combat"] form')
            ->form();

        $this->client->submit($form);
        self::assertResponseRedirects('/recompenses');
        $this->client->followRedirect();

        self::assertSelectorTextContains('[data-solde-pieces]', '1500 pièces');
        self::assertSelectorTextContains(
            '[data-profile-objectif="premier_combat"]',
            'Réclamé',
        );
        self::assertSelectorTextContains('.alert-success', 'Objectif validé');
    }
}
