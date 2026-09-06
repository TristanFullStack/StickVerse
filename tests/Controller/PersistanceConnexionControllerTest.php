<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PersistanceConnexionControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient([], ['HTTPS' => 'on']);
        $this->client->disableReboot();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);

        $database = $this->entityManager->getConnection()->fetchOne('SELECT DATABASE()');
        if (!is_string($database) || !str_ends_with($database, '_test')) {
            throw new LogicException('Le test HTTP doit utiliser une base terminant par "_test".');
        }

        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->getConnection()->rollBack();
        }
        $this->entityManager->clear();
        parent::tearDown();
    }

    public function testConnexionPersistanteCreeUnCookieSecurise(): void
    {
        $email = 'remember-'.bin2hex(random_bytes(6)).'@example.com';
        $user = (new User())->setEmail($email)->setPseudo('RememberJoueur')->setEmailVerifie(true);
        $user->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, 'mot-de-passe-solide'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $page = $this->client->request('GET', '/login');
        $this->client->submit(
            $page->selectButton('Se connecter')->form([
                '_username' => $email,
                '_password' => 'mot-de-passe-solide',
                '_remember_me' => '1',
            ]),
        );

        self::assertResponseRedirects('/wiki');
        $rememberCookie = null;
        foreach ($this->client->getResponse()->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'STICKVERSE_REMEMBER_ME') {
                $rememberCookie = $cookie;
                break;
            }
        }

        self::assertNotNull($rememberCookie, 'Le cookie remember-me doit être créé.');
        self::assertTrue($rememberCookie->isHttpOnly());
        self::assertTrue($rememberCookie->isSecure());
        self::assertSame('lax', strtolower((string) $rememberCookie->getSameSite()));
        self::assertGreaterThan(time() + 86400, $rememberCookie->getExpiresTime());

        $this->client->request('GET', '/logout');
        self::assertResponseRedirects('/wiki');
        $cookieSupprime = false;
        foreach ($this->client->getResponse()->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'STICKVERSE_REMEMBER_ME') {
                $cookieSupprime = true;
                self::assertNull($cookie->getValue());
                self::assertLessThanOrEqual(time(), $cookie->getExpiresTime());
            }
        }
        self::assertTrue($cookieSupprime, 'La déconnexion doit supprimer le cookie persistant.');
    }

    public function testConnexionSansCaseNeCreePasDeCookiePersistant(): void
    {
        $email = 'session-'.bin2hex(random_bytes(6)).'@example.com';
        $user = (new User())->setEmail($email)->setPseudo('SessionJoueur')->setEmailVerifie(true);
        $user->setPassword(static::getContainer()->get(UserPasswordHasherInterface::class)->hashPassword($user, 'mot-de-passe-solide'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $page = $this->client->request('GET', '/login');
        $csrfToken = (string) $page->filter('input[name="_csrf_token"]')->attr('value');
        $this->client->request('POST', '/login', [
            '_username' => $email,
            '_password' => 'mot-de-passe-solide',
            '_csrf_token' => $csrfToken,
        ]);

        self::assertResponseRedirects('/wiki');
        foreach ($this->client->getResponse()->headers->getCookies() as $cookie) {
            if ($cookie->getName() === 'STICKVERSE_REMEMBER_ME') {
                self::assertNull($cookie->getValue());
                self::assertLessThanOrEqual(time(), $cookie->getExpiresTime());
            }
        }
    }
}
