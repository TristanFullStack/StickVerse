<?php

namespace App\Tests\Controller;

use App\Entity\Actualite;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ActualiteControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $nomBase = $this->entityManager->getConnection()->fetchOne('SELECT DATABASE()');
        if (!is_string($nomBase) || !str_ends_with($nomBase, '_test')) { throw new LogicException('Le test doit utiliser la base de test.'); }
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager) && $this->entityManager->getConnection()->isTransactionActive()) { $this->entityManager->getConnection()->rollBack(); }
        parent::tearDown();
    }

    public function testAfficheUneActualitePubliee(): void
    {
        $actualite = (new Actualite())->setTitre('Nouveautés saison 1')->setSlug('nouveautes-saison-1-test')->setContenu('Nouveaux Stickmans et nouvelles caisses.')->setDatePublication(new \DateTimeImmutable('-1 minute'));
        $this->entityManager->persist($actualite); $this->entityManager->flush();

        $this->client->request('GET', '/actualites');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.actualite-card', 'Nouveautés saison 1');

        $this->client->request('GET', '/actualites/nouveautes-saison-1-test');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.actualite-content', 'Nouveaux Stickmans');
    }

    public function testGestionDesActualitesReserveeALAdministrateur(): void
    {
        $joueur = (new User())->setEmail('actu-joueur-'.bin2hex(random_bytes(4)).'@example.com')->setPassword('test');
        $this->entityManager->persist($joueur); $this->entityManager->flush(); $this->client->loginUser($joueur);
        $this->client->request('GET', '/admin/actualite');
        self::assertResponseStatusCodeSame(403);

        $admin = (new User())->setEmail('actu-admin-'.bin2hex(random_bytes(4)).'@example.com')->setPassword('test')->setRoles(['ROLE_ADMIN']);
        $this->entityManager->persist($admin); $this->entityManager->flush(); $this->client->loginUser($admin);
        $this->client->request('GET', '/admin/actualite');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Actualités');
    }
}
