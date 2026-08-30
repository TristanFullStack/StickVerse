<?php

namespace App\Tests\Controller;

use App\Entity\Combat;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
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
}
