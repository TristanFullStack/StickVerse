<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Entity\MouvementPieces;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AdministrationControllerTest extends WebTestCase
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

    public function testUnJoueurNePeutPasAccederALaConsole(): void
    {
        $joueur = (new User())
            ->setEmail('console-joueur-'.bin2hex(random_bytes(6)).'@example.com')
            ->setPassword('mot-de-passe-test');

        $this->entityManager->persist($joueur);
        $this->entityManager->flush();
        $this->client->loginUser($joueur);

        $this->client->request('GET', '/admin');

        self::assertResponseStatusCodeSame(403);
    }

    public function testLaConsoleEstAccessibleAUnAdministrateur(): void
    {
        $administrateur = (new User())
            ->setEmail('console-admin-'.bin2hex(random_bytes(6)).'@example.com')
            ->setPassword('mot-de-passe-test')
            ->setRoles(['ROLE_ADMIN']);

        $this->entityManager->persist($administrateur);
        $this->entityManager->flush();
        $this->client->loginUser($administrateur);

        $this->client->request('GET', '/admin');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Console d’administration');
        self::assertSelectorExists('[data-admin-console]');
        self::assertSelectorExists('[data-navigation-admin] a[href="/admin"]');
        self::assertSelectorTextContains('.dashboard-grid', 'Stickmans enregistrés');
    }

    public function testUnAdministrateurPeutCrediterUnJoueurAvecGive(): void
    {
        $administrateur = (new User())
            ->setEmail('console-give-admin-'.bin2hex(random_bytes(6)).'@example.com')
            ->setPassword('mot-de-passe-test')
            ->setRoles(['ROLE_ADMIN']);
        $joueur = (new User())
            ->setEmail('console-give-joueur-'.bin2hex(random_bytes(6)).'@example.com')
            ->setPseudo('CibleConsole'.strtoupper(bin2hex(random_bytes(2))))
            ->setPassword('mot-de-passe-test');

        $this->entityManager->persist($administrateur);
        $this->entityManager->persist($joueur);
        $this->entityManager->flush();
        $this->client->loginUser($administrateur);

        $crawler = $this->client->request('GET', '/admin');
        $form = $crawler->filter('[data-admin-console-form]')->form([
            'commande' => 'give '.$joueur->getPseudo().' 500',
        ]);
        $this->client->submit($form);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('[data-admin-console-result]', '500 pièces ajoutées');

        $this->entityManager->clear();
        $joueurActualise = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['pseudo' => $joueur->getPseudo()]);

        self::assertInstanceOf(User::class, $joueurActualise);
        self::assertSame(User::PIECES_DEPART + 500, $joueurActualise->getPieces());

        $mouvement = $this->entityManager
            ->getRepository(MouvementPieces::class)
            ->findOneBy(['utilisateur' => $joueurActualise]);

        self::assertInstanceOf(MouvementPieces::class, $mouvement);
        self::assertSame(MouvementPieces::TYPE_ADMIN_CREDIT, $mouvement->getType());
    }
}
