<?php

namespace App\Tests\Controller;

use App\Entity\Caisse;
use App\Entity\CaisseStickman;
use App\Entity\CollectionJeu;
use App\Entity\Inventaire;
use App\Entity\MouvementPieces;
use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\InventaireRepository;
use App\Repository\MouvementPiecesRepository;
use App\Repository\OuvertureCaisseRepository;
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

    public function testAfficheLeDetailEtLesProbabilitesDUneCaisse(): void
    {
        $caisse = $this->creerCaisse(120);
        self::assertNotNull($caisse->getId());

        $this->client->request('GET', '/caisses/'.$caisse->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', (string) $caisse->getNom());
        self::assertSelectorTextContains('.caisse-content-row', 'Probabilité : 100,00 %');
        self::assertSelectorTextNotContains('.caisse-content-row', 'Poids');
        self::assertSelectorExists('.caisse-content-row a[href^="/wiki/"]');
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
            'Caisse ouverte. Retrouve le résultat dans ta collection.',
        );
        self::assertSelectorTextContains(
            '[data-solde-pieces-page]',
            '880 pièces',
        );
        self::assertSame(880, $this->lireSolde($joueur));

        $mouvements = static::getContainer()
            ->get(MouvementPiecesRepository::class)
            ->findBy(['utilisateur' => $joueur]);
        self::assertCount(1, $mouvements);
        self::assertSame(-120, $mouvements[0]->getMontant());
        self::assertSame(
            MouvementPieces::TYPE_OUVERTURE_CAISSE_PAYANTE,
            $mouvements[0]->getType(),
        );

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

    public function testRetourneUnTirageServeurCompletPourLAnimation(): void
    {
        $joueur = $this->creerJoueur();
        $caisse = $this->creerCaisse(120);

        $resultat = $this->ouvrirJsonDepuisLaPage($joueur, $caisse);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertTrue(
            $this->client->getResponse()->headers->hasCacheControlDirective('no-store')
        );
        self::assertTrue($resultat['ok']);
        self::assertFalse($resultat['replayed']);
        self::assertSame(880, $resultat['wallet']['pieces']);
        self::assertTrue($resultat['reward']['isNew']);
        self::assertSame(1, $resultat['reward']['quantity']);
        self::assertSame($resultat['reward']['id'], $resultat['roulette'][0]['id']);
        self::assertArrayHasKey('power', $resultat['reward']);
        self::assertArrayHasKey('passives', $resultat['reward']);
        self::assertArrayNotHasKey('weight', $resultat['roulette'][0]);
        self::assertMatchesRegularExpression(
            '/^[a-f0-9]{64}$/',
            $resultat['nextOpeningToken'],
        );
    }

    public function testRejouerLaMemeRequeteNeDebiteEtNeRecompenseQuUneFois(): void
    {
        $joueur = $this->creerJoueur();
        $caisse = $this->creerCaisse(120);
        $caisseId = $caisse->getId();
        self::assertNotNull($caisseId);

        $this->client->loginUser($joueur);
        $page = $this->client->request('GET', '/caisses');
        $formulaire = $page
            ->filter(sprintf('form[action="/caisses/%d/ouvrir"]', $caisseId))
            ->form();
        $donnees = $formulaire->getPhpValues();

        $this->client->submit($formulaire, [], [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ]);
        $premier = json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        $this->client->request(
            'POST',
            '/caisses/'.$caisseId.'/ouvrir',
            $donnees,
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
            ],
        );
        $second = json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );

        self::assertResponseIsSuccessful();
        self::assertSame($premier['openingId'], $second['openingId']);
        self::assertTrue($second['replayed']);
        self::assertSame(880, $this->lireSolde($joueur));

        $inventaire = $this->trouverInventaire($joueur, $caisse);
        self::assertInstanceOf(Inventaire::class, $inventaire);
        self::assertSame(1, $inventaire->getQuantite());
        self::assertCount(1, static::getContainer()
            ->get(MouvementPiecesRepository::class)
            ->findBy(['utilisateur' => $joueur]));
        self::assertCount(1, static::getContainer()
            ->get(OuvertureCaisseRepository::class)
            ->findBy(['utilisateur' => $joueur]));
    }

    public function testLeNavigateurNePeutPasImposerLaRecompense(): void
    {
        $joueur = $this->creerJoueur();
        $caisse = $this->creerCaisse(120);
        $stickmanAttendu = $caisse->getContenus()->first()->getStickman();
        self::assertInstanceOf(Stickman::class, $stickmanAttendu);

        $resultat = $this->ouvrirJsonDepuisLaPage(
            $joueur,
            $caisse,
            ['reward_id' => '999999', 'stickman' => '999999'],
        );

        self::assertResponseIsSuccessful();
        self::assertSame($stickmanAttendu->getId(), $resultat['reward']['id']);
    }

    public function testLaPagePrepareUnJetonUniqueEtLOverlayAccessible(): void
    {
        $joueur = $this->creerJoueur();
        $caisse = $this->creerCaisse(120);
        $this->client->loginUser($joueur);

        $page = $this->client->request('GET', '/caisses');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString(
            'no-store',
            (string) $this->client->getResponse()->headers->get('Cache-Control'),
        );
        self::assertSelectorExists('[data-controller="caisse-ouverture"]');
        self::assertSelectorExists('[data-caisse-ouverture-target="overlay"]');
        self::assertSelectorExists('.crate-opening-dialog[role="dialog"]');
        self::assertSelectorExists('[data-caisse-ouverture-target="loadingPhase"]');
        self::assertSelectorExists('[data-caisse-ouverture-target="roulettePhase"][hidden]');
        $jeton = $page->filter(sprintf(
            'form[action="/caisses/%d/ouvrir"] input[name="_ouverture"]',
            $caisse->getId(),
        ))->attr('value');
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $jeton);
        self::assertSelectorExists(sprintf(
            'form[action="/caisses/%d/ouvrir"] button[data-opening-submit][data-action="click->caisse-ouverture#ouvrir"]',
            $caisse->getId(),
        ));
    }

    public function testRetourneLaProgressionEtLaCompletionDeCollection(): void
    {
        $joueur = $this->creerJoueur();
        $caisse = $this->creerCaisse(120);
        $suffixe = bin2hex(random_bytes(5));
        $collection = (new CollectionJeu())
            ->setNom('Collection test '.$suffixe)
            ->setSlug('collection-test-'.$suffixe)
            ->setDescription('Collection terminée par une ouverture de test.')
            ->setSaison(1)
            ->setStatutActif(true);
        $stickman = $caisse->getContenus()->first()->getStickman();
        self::assertInstanceOf(Stickman::class, $stickman);
        $caisse->setCollectionJeu($collection);
        $stickman->setCollectionJeu($collection);
        $this->entityManager->persist($collection);
        $this->entityManager->flush();

        $resultat = $this->ouvrirJsonDepuisLaPage($joueur, $caisse);

        self::assertSame($collection->getNom(), $resultat['collection']['name']);
        self::assertSame(1, $resultat['collection']['owned']);
        self::assertSame(1, $resultat['collection']['total']);
        self::assertTrue($resultat['collection']['complete']);
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

    /**
     * @param array<string, string> $donneesSupplementaires
     * @return array<string, mixed>
     */
    private function ouvrirJsonDepuisLaPage(
        User $joueur,
        Caisse $caisse,
        array $donneesSupplementaires = [],
    ): array {
        $caisseId = $caisse->getId();
        self::assertNotNull($caisseId);

        $this->client->loginUser($joueur);
        $page = $this->client->request('GET', '/caisses');
        $formulaire = $page
            ->filter(sprintf('form[action="/caisses/%d/ouvrir"]', $caisseId))
            ->form();

        $entetes = [
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        ];

        if ($donneesSupplementaires === []) {
            $this->client->submit($formulaire, [], $entetes);
        } else {
            $this->client->request(
                'POST',
                '/caisses/'.$caisseId.'/ouvrir',
                array_replace($formulaire->getPhpValues(), $donneesSupplementaires),
                [],
                $entetes,
            );
        }

        return json_decode(
            (string) $this->client->getResponse()->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
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
