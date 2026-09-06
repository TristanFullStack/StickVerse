<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class VerificationEmailControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
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

    #[DataProvider('choixConnexionApresConfirmation')]
    public function testConfirmationRespecteLeChoixDeConnexionAutomatique(bool $connexionAutomatique, string $route): void
    {
        $token = str_repeat($connexionAutomatique ? 'a' : 'b', 64);
        $user = (new User())
            ->setEmail(('verification-'.bin2hex(random_bytes(6))).'@example.com')
            ->setPseudo($connexionAutomatique ? 'VerificationAuto' : 'VerificationManuelle')
            ->setPassword('test-password-hash')
            ->setEmailVerifie(false)
            ->setConnexionAutomatiqueApresVerification($connexionAutomatique)
            ->preparerVerificationEmail($token, new \DateTimeImmutable('+1 day'));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $this->client->request('GET', '/verification-email/'.$token);
        self::assertResponseRedirects(
            $connexionAutomatique
                ? $route
                : '/login?_username='.$user->getEmail(),
        );

        $this->client->followRedirect();
        self::assertResponseIsSuccessful();

        $this->entityManager->clear();
        $actual = $this->entityManager->find(User::class, $user->getId());
        self::assertInstanceOf(User::class, $actual);
        self::assertTrue($actual->isEmailVerifie());
        self::assertFalse($actual->doitSeConnecterAutomatiquementApresVerification());

        if ($connexionAutomatique) {
            self::assertSelectorTextContains('body', $actual->getPseudo());
        } else {
            self::assertSame(
                $actual->getEmail(),
                $this->client->getCrawler()->filter('input#username')->attr('value'),
            );
            self::assertSelectorTextContains('body', 'Tu peux maintenant te connecter.');
        }
    }

    public static function choixConnexionApresConfirmation(): iterable
    {
        yield 'connexion automatique' => [true, '/home'];
        yield 'connexion manuelle' => [false, '/login'];
    }
}
