<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SaisonControllerTest extends WebTestCase
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
            throw new LogicException('Le test HTTP doit utiliser une base terminant par "_test".');
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
        $this->client->request('GET', '/saison');

        self::assertResponseRedirects('/login');
    }

    public function testAfficheLaSaisonActiveAuJoueur(): void
    {
        $joueur = (new User())
            ->setEmail('saison-http@example.com')
            ->setPassword('mot-de-passe-test');
        $this->entityManager->persist($joueur);
        $this->entityManager->flush();

        $this->client->loginUser($joueur);
        $this->client->request('GET', '/saison');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Saison en cours');
        self::assertSelectorTextContains('.site-content', 'Saison 1');
        self::assertSelectorExists('progress');
        self::assertSelectorNotExists('.stickman-card-link--non-obtenue');
        self::assertSelectorExists('.site-navigation a[href="/saison"][aria-current="page"]');
    }
}
