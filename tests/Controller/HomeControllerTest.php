<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
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

    public function testAfficheLaPresentationAuVisiteur(): void
    {
        $this->client->request('GET', '/home');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Bienvenue sur StickVerse');
        self::assertSelectorExists('a[href="/register"]');
        self::assertSelectorExists('a[href="/login"]');
        self::assertSelectorNotExists('.dashboard-section');
    }

    public function testAfficheLeTableauDeBordAuJoueur(): void
    {
        $joueur = (new User())
            ->setEmail('tableau-de-bord@example.com')
            ->setPassword('mot-de-passe-test');

        $this->entityManager->persist($joueur);
        $this->entityManager->flush();

        $this->client->loginUser($joueur);
        $this->client->request('GET', '/home');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Tableau de bord');
        self::assertSelectorTextContains(
            '.site-content',
            '0 Stickman(s) différent(s)',
        );
        self::assertSelectorTextContains(
            '.site-content',
            'Aucune équipe enregistrée.',
        );
        self::assertSelectorTextContains(
            '.site-content',
            'Aucun combat actif.',
        );
        self::assertSelectorTextContains(
            '.site-content',
            'Aucun combat terminé pour le moment.',
        );
        self::assertSelectorTextContains(
            '[data-dashboard-pieces]',
            '1000 pièces disponibles',
        );
        self::assertSelectorTextContains(
            '[data-solde-pieces]',
            '1000 pièces',
        );
        self::assertSelectorExists('a[href="/ma-collection"]');
        self::assertSelectorExists('a[href="/equipe"]');
        self::assertSelectorExists('a[href="/combats"]');
        self::assertSelectorExists('a[href="/classement"]');
    }
}
