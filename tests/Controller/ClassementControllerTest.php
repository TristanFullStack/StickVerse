<?php

namespace App\Tests\Controller;

use App\Entity\ClassementSaisonJoueur;
use App\Entity\CollectionJeu;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ClassementControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
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

    public function testAfficheTousLesJoueursDansLOrdreElo(): void
    {
        $joueurMieuxClasse = (new User())
            ->setEmail('classement-haut-'.bin2hex(random_bytes(4)).'@example.com')
            ->setPseudo('AlphaClassement'.bin2hex(random_bytes(2)))
            ->setPassword('mot-de-passe-test')
            ->setElo(1200);
        $joueurMoinsClasse = (new User())
            ->setEmail('classement-bas-'.bin2hex(random_bytes(4)).'@example.com')
            ->setPseudo('BetaClassement'.bin2hex(random_bytes(2)))
            ->setPassword('mot-de-passe-test')
            ->setElo(900);

        $this->entityManager->persist($joueurMoinsClasse);
        $this->entityManager->persist($joueurMieuxClasse);

        $saison = (new CollectionJeu())
            ->setNom('Saison de classement')
            ->setSlug('saison-classement-'.bin2hex(random_bytes(4)))
            ->setDescription('Saison utilisée par le test du classement.')
            ->setSaison(99)
            ->setStatutActif(true);
        $classementHaut = (new ClassementSaisonJoueur(
            $joueurMieuxClasse,
            $saison,
        ))->enregistrerResultat(25, 1.0);
        $classementBas = (new ClassementSaisonJoueur(
            $joueurMoinsClasse,
            $saison,
        ))->enregistrerResultat(-25, 0.0);

        $this->entityManager->persist($saison);
        $this->entityManager->persist($classementHaut);
        $this->entityManager->persist($classementBas);
        $this->entityManager->flush();

        $this->client->loginUser($joueurMieuxClasse);

        $this->client->request('GET', '/classement');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Classement ELO');
        self::assertSelectorTextContains(
            '#classement-saison-titre',
            'Saison 99',
        );
        self::assertSelectorCount(2, '[data-classement-saison] tbody tr');
        self::assertSame(
            $joueurMieuxClasse->getPseudo(),
            $this->client->getCrawler()->filter('[data-classement-saison] tbody tr')->eq(0)->filter('td')->eq(1)->text(),
        );
        self::assertSame(
            '525',
            $this->client->getCrawler()->filter('[data-classement-saison] tbody tr')->eq(0)->filter('td')->eq(2)->text(),
        );
        self::assertSelectorTextContains('[data-ma-division]', 'Bronze');
        self::assertSelectorTextContains('[data-ma-division]', '100 pièces');
        self::assertSelectorTextContains(
            '[data-classement-saison] tbody tr:first-child [data-division]',
            'Bronze',
        );
        self::assertSelectorCount(2, '[data-classement] tbody tr');
        self::assertSame(
            $joueurMieuxClasse->getPseudo(),
            $this->client->getCrawler()->filter('[data-classement] tbody tr')->eq(0)->filter('td')->eq(1)->text(),
        );
        self::assertSame(
            $joueurMoinsClasse->getPseudo(),
            $this->client->getCrawler()->filter('[data-classement] tbody tr')->eq(1)->filter('td')->eq(1)->text(),
        );
    }

    public function testReclameLaRecompenseDUneSaisonTerminee(): void
    {
        $joueur = (new User())
            ->setEmail('classement-recompense-'.bin2hex(random_bytes(4)).'@example.com')
            ->setPseudo('JoueurRecompense'.bin2hex(random_bytes(2)))
            ->setPassword('mot-de-passe-test');
        $saison = (new CollectionJeu())
            ->setNom('Saison terminée')
            ->setSlug('saison-terminee-'.bin2hex(random_bytes(4)))
            ->setDescription('Saison terminée utilisée par le test HTTP.')
            ->setSaison(98)
            ->setStatutActif(false)
            ->setDateFin(new DateTimeImmutable('2026-08-31 23:59:59'));
        $classement = (new ClassementSaisonJoueur($joueur, $saison))
            ->enregistrerResultat(200, 1.0);

        $this->entityManager->persist($joueur);
        $this->entityManager->persist($saison);
        $this->entityManager->persist($classement);
        $this->entityManager->flush();

        $this->client->loginUser($joueur);
        $page = $this->client->request('GET', '/classement?saison=98');
        $formulaire = $page->filter('[data-recompense-saison]')->form();

        $this->client->submit($formulaire);

        self::assertResponseRedirects('/classement?saison=98');
        $this->client->followRedirect();
        self::assertSame(
            1100,
            (int) $this->entityManager->getConnection()->fetchOne(
                'SELECT pieces FROM user WHERE id = ?',
                [$joueur->getId()],
            ),
        );
        self::assertSelectorNotExists('[data-recompense-saison]');
        self::assertSelectorExists('[data-recompense-saison-reclamee]');
        self::assertSelectorTextContains(
            '.alert-success',
            'Récompense de la Saison 98 récupérée',
        );
    }
}
