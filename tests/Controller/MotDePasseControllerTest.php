<?php

namespace App\Tests\Controller;

use App\Entity\ReinitialisationMotDePasse;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class MotDePasseControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();

        $entityManager = static::getContainer()->get(
            EntityManagerInterface::class
        );
        $passwordHasher = static::getContainer()->get(
            UserPasswordHasherInterface::class
        );

        self::assertInstanceOf(
            EntityManagerInterface::class,
            $entityManager,
        );
        self::assertInstanceOf(
            UserPasswordHasherInterface::class,
            $passwordHasher,
        );

        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;

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

    public function testModifieLeMotDePasseDuJoueurConnecte(): void
    {
        $ancienMotDePasse = 'ancien-mot-de-passe';
        $nouveauMotDePasse = 'nouveau-mot-de-passe';
        $joueur = $this->creerUtilisateur(
            'modifier-mot-de-passe@example.com',
            $ancienMotDePasse,
        );
        $joueurId = $joueur->getId();

        self::assertNotNull($joueurId);

        $this->client->loginUser($joueur);
        $page = $this->client->request(
            'GET',
            '/profil/mot-de-passe',
        );

        self::assertResponseIsSuccessful();

        $this->client->submit(
            $page->selectButton('Modifier le mot de passe')->form([
                'modifier_mot_de_passe[motDePasseActuel]' =>
                    $ancienMotDePasse,
                'modifier_mot_de_passe[nouveauMotDePasse][first]' =>
                    $nouveauMotDePasse,
                'modifier_mot_de_passe[nouveauMotDePasse][second]' =>
                    $nouveauMotDePasse,
            ])
        );

        self::assertResponseRedirects('/profil');
        $this->client->followRedirect();
        self::assertSelectorTextContains(
            '[role="status"]',
            'mot de passe a bien été modifié',
        );

        $this->entityManager->clear();
        $joueurRecharge = $this->entityManager->find(
            User::class,
            $joueurId,
        );

        self::assertInstanceOf(User::class, $joueurRecharge);
        self::assertTrue(
            $this->passwordHasher->isPasswordValid(
                $joueurRecharge,
                $nouveauMotDePasse,
            )
        );
        self::assertFalse(
            $this->passwordHasher->isPasswordValid(
                $joueurRecharge,
                $ancienMotDePasse,
            )
        );
    }

    public function testRefuseUnMotDePasseActuelIncorrect(): void
    {
        $joueur = $this->creerUtilisateur(
            'mot-de-passe-incorrect@example.com',
            'mot-de-passe-valide',
        );

        $this->client->loginUser($joueur);
        $page = $this->client->request(
            'GET',
            '/profil/mot-de-passe',
        );

        $this->client->submit(
            $page->selectButton('Modifier le mot de passe')->form([
                'modifier_mot_de_passe[motDePasseActuel]' =>
                    'mauvais-mot-de-passe',
                'modifier_mot_de_passe[nouveauMotDePasse][first]' =>
                    'nouveau-mot-de-passe',
                'modifier_mot_de_passe[nouveauMotDePasse][second]' =>
                    'nouveau-mot-de-passe',
            ])
        );

        self::assertResponseIsUnprocessable();
        self::assertSelectorTextContains(
            'form',
            'Le mot de passe actuel est incorrect.',
        );
    }

    public function testDemandeUnLienSansRevelerLeCompte(): void
    {
        $messageEnvoye = null;
        $mailer = $this->createMock(MailerInterface::class);
        $mailer
            ->expects(self::once())
            ->method('send')
            ->willReturnCallback(
                static function ($message) use (&$messageEnvoye): void {
                    $messageEnvoye = $message;
                }
            );
        static::getContainer()->set(MailerInterface::class, $mailer);

        $joueur = $this->creerUtilisateur(
            'recuperation@example.com',
            'mot-de-passe-initial',
        );

        $page = $this->client->request(
            'GET',
            '/mot-de-passe/oublie',
        );
        $this->client->submit(
            $page->selectButton('Envoyer le lien')->form([
                'demande_reinitialisation_mot_de_passe[email]' =>
                    $joueur->getUserIdentifier(),
            ])
        );

        self::assertResponseRedirects('/mot-de-passe/oublie');
        $this->client->followRedirect();
        self::assertSelectorTextContains(
            '[role="status"]',
            'Si cette adresse correspond à un compte',
        );
        self::assertSame(
            1,
            $this->entityManager
                ->getRepository(ReinitialisationMotDePasse::class)
                ->count(['utilisateur' => $joueur]),
        );
        self::assertInstanceOf(TemplatedEmail::class, $messageEnvoye);
        self::assertSame(
            'Réinitialisation de ton mot de passe StickVerse',
            $messageEnvoye->getSubject(),
        );
        self::assertSame(
            'recuperation@example.com',
            $messageEnvoye->getTo()[0]->getAddress(),
        );
        self::assertStringContainsString(
            '/mot-de-passe/reinitialiser/',
            $messageEnvoye->getContext()['lien_reinitialisation'],
        );
    }

    public function testNeRevelePasUneAdresseInconnue(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::never())->method('send');
        static::getContainer()->set(MailerInterface::class, $mailer);
        $nombreDemandesAvant = $this->entityManager
            ->getRepository(ReinitialisationMotDePasse::class)
            ->count([]);

        $page = $this->client->request(
            'GET',
            '/mot-de-passe/oublie',
        );
        $this->client->submit(
            $page->selectButton('Envoyer le lien')->form([
                'demande_reinitialisation_mot_de_passe[email]' =>
                    'adresse-inconnue@example.com',
            ])
        );

        self::assertResponseRedirects('/mot-de-passe/oublie');
        $this->client->followRedirect();
        self::assertSelectorTextContains(
            '[role="status"]',
            'Si cette adresse correspond à un compte',
        );
        self::assertSame(
            $nombreDemandesAvant,
            $this->entityManager
                ->getRepository(ReinitialisationMotDePasse::class)
                ->count([]),
        );
    }

    public function testReinitialisePuisInvalideLeJeton(): void
    {
        $nouveauMotDePasse = 'mot-de-passe-reinitialise';
        $joueur = $this->creerUtilisateur(
            'jeton-reinitialisation@example.com',
            'mot-de-passe-initial',
        );
        $jeton = bin2hex(random_bytes(32));
        $demande = new ReinitialisationMotDePasse($joueur, $jeton);

        $this->entityManager->persist($demande);
        $this->entityManager->flush();

        $joueurId = $joueur->getId();
        $demandeId = $demande->getId();

        self::assertNotNull($joueurId);
        self::assertNotNull($demandeId);

        $page = $this->client->request(
            'GET',
            '/mot-de-passe/reinitialiser/'.$jeton,
        );

        self::assertResponseIsSuccessful();

        $this->client->submit(
            $page
                ->selectButton('Enregistrer le nouveau mot de passe')
                ->form([
                    'reinitialiser_mot_de_passe[nouveauMotDePasse][first]' =>
                        $nouveauMotDePasse,
                    'reinitialiser_mot_de_passe[nouveauMotDePasse][second]' =>
                        $nouveauMotDePasse,
                ])
        );

        self::assertResponseRedirects('/login');
        $this->client->followRedirect();
        self::assertSelectorTextContains(
            '[role="status"]',
            'mot de passe a été réinitialisé',
        );

        $this->entityManager->clear();
        $joueurRecharge = $this->entityManager->find(
            User::class,
            $joueurId,
        );
        $demandeRechargee = $this->entityManager->find(
            ReinitialisationMotDePasse::class,
            $demandeId,
        );

        self::assertInstanceOf(User::class, $joueurRecharge);
        self::assertInstanceOf(
            ReinitialisationMotDePasse::class,
            $demandeRechargee,
        );
        self::assertTrue(
            $this->passwordHasher->isPasswordValid(
                $joueurRecharge,
                $nouveauMotDePasse,
            )
        );
        self::assertNotNull($demandeRechargee->getDateUtilisation());

        $this->client->request(
            'GET',
            '/mot-de-passe/reinitialiser/'.$jeton,
        );
        self::assertResponseStatusCodeSame(400);
        self::assertSelectorTextContains('h1', 'Lien invalide ou expiré');
    }

    private function creerUtilisateur(
        string $email,
        string $motDePasse,
    ): User {
        $utilisateur = (new User())
            ->setEmail($email)
            ->setPassword('provisoire');
        $utilisateur->setPassword(
            $this->passwordHasher->hashPassword(
                $utilisateur,
                $motDePasse,
            )
        );

        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();

        return $utilisateur;
    }
}
