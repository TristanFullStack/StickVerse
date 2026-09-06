<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Repository\InventaireRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ParcoursNouveauJoueurControllerTest extends WebTestCase
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

    public function testInscriptionPrepareLePremierParcoursEtDemandeLaConfirmation(): void
    {
        $email = sprintf(
            'nouveau-joueur-%s@example.com',
            bin2hex(random_bytes(6)),
        );

        $this->client->request('GET', '/register');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Créer mon compte', [
            'registration_form[pseudo]' => 'NouveauJoueur',
            'registration_form[email]' => $email,
            'registration_form[plainPassword]' => 'mot-de-passe-solide',
            'registration_form[agreeTerms]' => '1',
        ]);

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            '.auth-alert--success',
            'Compte créé ! Consulte ton adresse e-mail',
        );

        $userRepository = static::getContainer()->get(UserRepository::class);
        $inventaireRepository = static::getContainer()->get(
            InventaireRepository::class
        );
        $utilisateur = $userRepository->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $utilisateur);
        self::assertSame('NouveauJoueur', $utilisateur->getPseudo());
        self::assertFalse($utilisateur->isEmailVerifie());

        $inventaires = $inventaireRepository->findBy([
            'utilisateur' => $utilisateur,
        ]);

        self::assertCount(0, $inventaires);
        self::assertSame(5, $utilisateur->getCaissesPremiersRenforts());

        $this->client->request('GET', '/equipe');
        self::assertResponseRedirects('/login');
    }

    public function testInscriptionRefuseUnPseudoDejaUtilise(): void
    {
        $pseudo = 'PseudoInscription';
        $utilisateurExistant = (new User())
            ->setPseudo($pseudo)
            ->setEmail(
                'pseudo-existant-'.bin2hex(random_bytes(5)).'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        $this->entityManager->persist($utilisateurExistant);
        $this->entityManager->flush();

        $this->client->request('GET', '/register');
        $this->client->submitForm('Créer mon compte', [
            'registration_form[pseudo]' => $pseudo,
            'registration_form[email]' =>
                'nouvelle-adresse-'.bin2hex(random_bytes(5)).'@example.com',
            'registration_form[plainPassword]' => 'mot-de-passe-solide',
            'registration_form[agreeTerms]' => '1',
        ]);

        self::assertResponseIsUnprocessable();
        self::assertSelectorTextContains(
            'form',
            'Ce pseudo est déjà utilisé.',
        );
        self::assertSame(
            1,
            static::getContainer()
                ->get(UserRepository::class)
                ->count(['pseudo' => $pseudo]),
        );
    }

    public function testInscriptionMemoriseLaConnexionUniquementSurDemande(): void
    {
        $email = sprintf('connexion-apres-confirmation-%s@example.com', bin2hex(random_bytes(6)));

        $this->client->request('GET', '/register');
        $this->client->submitForm('Créer mon compte', [
            'registration_form[pseudo]' => 'ConnexionAuto',
            'registration_form[email]' => $email,
            'registration_form[plainPassword]' => 'mot-de-passe-solide',
            'registration_form[agreeTerms]' => '1',
            'registration_form[connexionAutomatique]' => '1',
        ]);

        self::assertResponseRedirects('/login');

        $utilisateur = static::getContainer()
            ->get(UserRepository::class)
            ->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $utilisateur);
        self::assertTrue($utilisateur->doitSeConnecterAutomatiquementApresVerification());
    }

}
