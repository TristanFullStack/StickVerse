<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CompteControllerTest extends WebTestCase
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

    public function testSuppressionRedirigeSansErreurEtInvalideLaSession(): void
    {
        $email = 'suppression-'.bin2hex(random_bytes(6)).'@example.com';
        $motDePasse = 'mot-de-passe-solide';
        $joueur = (new User())
            ->setPseudo('JoueurSuppression')
            ->setEmail($email);
        $joueur->setPassword(
            static::getContainer()
                ->get(UserPasswordHasherInterface::class)
                ->hashPassword($joueur, $motDePasse),
        );

        $this->entityManager->persist($joueur);
        $this->entityManager->flush();
        $this->client->loginUser($joueur);

        $this->client->request('GET', '/profil/supprimer');
        self::assertResponseIsSuccessful();
        self::assertSelectorExists('form[name="supprimer_compte"]');

        $this->client->submitForm('Supprimer définitivement mon compte', [
            'supprimer_compte[motDePasse]' => $motDePasse,
            'supprimer_compte[confirmation]' => '1',
        ]);

        self::assertResponseRedirects('/home');
        $this->client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            '.auth-alert--success',
            'Ton compte a été supprimé définitivement.',
        );
        self::assertSelectorNotExists('.site-account a[href="/profil"]');
        self::assertNull(
            static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]),
        );
    }

    public function testSuppressionRefuseUnMotDePasseIncorrect(): void
    {
        $joueur = (new User())
            ->setPseudo('JoueurSuppressionRefusee')
            ->setEmail('suppression-refusee-'.bin2hex(random_bytes(6)).'@example.com')
            ->setPassword('mot-de-passe-hashe');
        $this->entityManager->persist($joueur);
        $this->entityManager->flush();
        $this->client->loginUser($joueur);

        $this->client->request('GET', '/profil/supprimer');
        $this->client->submitForm('Supprimer définitivement mon compte', [
            'supprimer_compte[motDePasse]' => 'mauvais-mot-de-passe',
            'supprimer_compte[confirmation]' => '1',
        ]);

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('form', 'Le mot de passe actuel est incorrect.');
    }
}
