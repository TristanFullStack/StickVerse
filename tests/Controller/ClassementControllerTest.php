<?php

namespace App\Tests\Controller;

use App\Entity\User;
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
        $this->entityManager->flush();

        $this->client->request('GET', '/classement');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Classement ELO');
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
}
