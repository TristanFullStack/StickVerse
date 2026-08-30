<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PseudoControllerTest extends WebTestCase
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

    public function testRedirigeLeVisiteurVersLaConnexion(): void
    {
        $this->client->request('GET', '/profil/pseudo');

        self::assertResponseRedirects('/login');
    }

    public function testModifieLePseudoPublic(): void
    {
        $joueur = $this->creerJoueur('AncienPseudo');

        $this->client->loginUser($joueur);
        $page = $this->client->request('GET', '/profil/pseudo');

        self::assertResponseIsSuccessful();
        self::assertInputValueSame(
            'modifier_pseudo[pseudo]',
            'AncienPseudo',
        );

        $this->client->submit(
            $page->selectButton('Modifier le pseudo')->form([
                'modifier_pseudo[pseudo]' => 'NouveauPseudo',
            ])
        );

        self::assertResponseRedirects('/profil');
        $this->client->followRedirect();
        self::assertSelectorTextContains(
            '[role="status"]',
            'pseudo a bien été modifié',
        );
        self::assertSelectorTextContains(
            '[data-profile-pseudo]',
            'NouveauPseudo',
        );
    }

    public function testRefuseUnPseudoDejaUtilise(): void
    {
        $joueur = $this->creerJoueur('PseudoInitial');
        $this->creerJoueur('PseudoOccupe');

        $this->client->loginUser($joueur);
        $page = $this->client->request('GET', '/profil/pseudo');
        $this->client->submit(
            $page->selectButton('Modifier le pseudo')->form([
                'modifier_pseudo[pseudo]' => 'PseudoOccupe',
            ])
        );

        self::assertResponseIsUnprocessable();
        self::assertSelectorTextContains(
            'form',
            'Ce pseudo est déjà utilisé.',
        );
        self::assertSame('PseudoInitial', $joueur->getPseudo());
    }

    private function creerJoueur(string $pseudo): User
    {
        $joueur = (new User())
            ->setPseudo($pseudo)
            ->setEmail(
                strtolower($pseudo)
                .'-'.bin2hex(random_bytes(5))
                .'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        $this->entityManager->persist($joueur);
        $this->entityManager->flush();

        return $joueur;
    }
}
