<?php

namespace App\Tests\Controller;

use App\Entity\Caisse;
use App\Entity\CaisseStickman;
use App\Entity\Inventaire;
use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\InventaireRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CaissePubliqueControllerTest extends WebTestCase
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

    public function testAfficheLesPrixSansExposerUnSoldeAuVisiteur(): void
    {
        $caisse = $this->creerCaisse(120);

        $this->client->request('GET', '/caisses');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            '.caisse-card',
            'Prix : 120 pièces',
        );
        self::assertSelectorNotExists('[data-solde-pieces-page]');
        self::assertSelectorExists('.caisse-card a[href="/login"]');
        self::assertNotNull($caisse->getId());
    }

    public function testPayeLaCaisseEtAjouteLeStickman(): void
    {
        $joueur = $this->creerJoueur();
        $caisse = $this->creerCaisse(120);

        $this->ouvrirDepuisLaPage($joueur, $caisse);

        self::assertResponseRedirects('/caisses');
        $this->client->followRedirect();
        self::assertSelectorTextContains(
            '.flash-success',
            'Solde restant : 880 pièces',
        );
        self::assertSelectorTextContains(
            '[data-solde-pieces-page]',
            '880 pièces',
        );
        self::assertSame(880, $this->lireSolde($joueur));

        $inventaire = $this->trouverInventaire($joueur, $caisse);
        self::assertInstanceOf(Inventaire::class, $inventaire);
        self::assertSame(1, $inventaire->getQuantite());
    }

    public function testDeuxOuverturesDebitentDeuxFoisEtAugmententLaQuantite(): void
    {
        $joueur = $this->creerJoueur();
        $caisse = $this->creerCaisse(120);

        $this->ouvrirDepuisLaPage($joueur, $caisse);
        self::assertResponseRedirects('/caisses');
        $this->ouvrirDepuisLaPage($joueur, $caisse);
        self::assertResponseRedirects('/caisses');

        self::assertSame(760, $this->lireSolde($joueur));
        $inventaire = $this->trouverInventaire($joueur, $caisse);
        self::assertInstanceOf(Inventaire::class, $inventaire);
        self::assertSame(2, $inventaire->getQuantite());
    }

    public function testRefuseLaCaisseSansAssezDePieces(): void
    {
        $joueur = $this->creerJoueur(50);
        $caisse = $this->creerCaisse(120);

        $this->ouvrirDepuisLaPage($joueur, $caisse);

        self::assertResponseRedirects('/caisses');
        $this->client->followRedirect();
        self::assertSelectorTextContains(
            '.flash-error',
            'pas assez de pièces',
        );
        self::assertSame(50, $this->lireSolde($joueur));
        self::assertNull($this->trouverInventaire($joueur, $caisse));
    }

    public function testCaisseVideNeDebiteAucunePiece(): void
    {
        $joueur = $this->creerJoueur();
        $caisse = $this->creerCaisse(120, false);

        $this->ouvrirDepuisLaPage($joueur, $caisse);

        self::assertResponseRedirects('/caisses');
        $this->client->followRedirect();
        self::assertSelectorTextContains(
            '.flash-error',
            'ne contient aucun Stickman',
        );
        self::assertSame(1000, $this->lireSolde($joueur));
    }

    private function creerJoueur(int $pieces = 1000): User
    {
        $joueur = (new User())
            ->setEmail(
                'caisse-'.bin2hex(random_bytes(6)).'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        if ($pieces < User::PIECES_DEPART) {
            self::assertTrue(
                $joueur->debiterPieces(User::PIECES_DEPART - $pieces)
            );
        }

        $this->entityManager->persist($joueur);
        $this->entityManager->flush();

        return $joueur;
    }

    private function creerCaisse(
        int $prix,
        bool $avecContenu = true,
    ): Caisse {
        $suffixe = bin2hex(random_bytes(5));
        $caisse = (new Caisse())
            ->setNom('Caisse test '.$suffixe)
            ->setSlug('caisse-test-'.$suffixe)
            ->setDescription('Caisse utilisée par le test J69.')
            ->setImage('caisse-test.png')
            ->setPrix($prix)
            ->setStatutActif(true);

        $this->entityManager->persist($caisse);

        if ($avecContenu) {
            $stickman = (new Stickman())
                ->setNom('Stickman test '.$suffixe)
                ->setSlug('stickman-test-'.$suffixe)
                ->setDescription('Stickman obtenu pendant le test.')
                ->setImage('stickman-test.png')
                ->setRarete(1)
                ->setPv(10)
                ->setAttaque(2)
                ->setDefense(2)
                ->setStatutActif(true);
            $contenu = (new CaisseStickman())
                ->setStickman($stickman)
                ->setPoids(1);
            $caisse->addContenu($contenu);

            $this->entityManager->persist($stickman);
            $this->entityManager->persist($contenu);
        }

        $this->entityManager->flush();

        return $caisse;
    }

    private function ouvrirDepuisLaPage(
        User $joueur,
        Caisse $caisse,
    ): void {
        $caisseId = $caisse->getId();
        self::assertNotNull($caisseId);

        $this->client->loginUser($joueur);
        $page = $this->client->request('GET', '/caisses');
        $formulaire = $page
            ->filter(sprintf(
                'form[action="/caisses/%d/ouvrir"]',
                $caisseId,
            ))
            ->form();

        $this->client->submit($formulaire);
    }

    private function trouverInventaire(
        User $joueur,
        Caisse $caisse,
    ): ?Inventaire {
        $contenu = $caisse->getContenus()->first();

        if (!$contenu instanceof CaisseStickman) {
            return null;
        }

        $stickman = $contenu->getStickman();

        if (!$stickman instanceof Stickman) {
            return null;
        }

        $repository = static::getContainer()->get(
            InventaireRepository::class
        );

        return $repository->findOneBy([
            'utilisateur' => $joueur,
            'stickman' => $stickman,
        ]);
    }

    private function lireSolde(User $joueur): int
    {
        $joueurId = $joueur->getId();
        self::assertNotNull($joueurId);

        return (int) $this->entityManager
            ->getConnection()
            ->fetchOne(
                'SELECT pieces FROM user WHERE id = ?',
                [$joueurId],
            );
    }
}
