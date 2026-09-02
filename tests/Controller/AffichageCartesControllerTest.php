<?php

namespace App\Tests\Controller;

use App\Entity\CollectionJeu;
use App\Entity\Stickman;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class AffichageCartesControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->client->disableReboot();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $nomBase = $this->entityManager->getConnection()->fetchOne('SELECT DATABASE()');
        if (!is_string($nomBase) || !str_ends_with($nomBase, '_test')) {
            throw new LogicException('Le test doit utiliser la base de test.');
        }
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        if (isset($this->entityManager) && $this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->getConnection()->rollBack();
        }
        parent::tearDown();
    }

    public function testLeWikiConserveLaCouleurDUneCarteNonPossedee(): void
    {
        [, $stickman] = $this->creerCollectionEtStickman();

        $this->client->request('GET', '/wiki');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href="/wiki/'.$stickman->getSlug().'"]');
        self::assertSelectorNotExists('a[href="/wiki/'.$stickman->getSlug().'"][class~="stickman-card-link--non-obtenue"]');
    }

    public function testSeuleMaCollectionGriseUneCarteManquante(): void
    {
        [, $stickman] = $this->creerCollectionEtStickman();
        $joueur = (new User())->setEmail('cartes-'.bin2hex(random_bytes(5)).'@example.com')->setPassword('test');
        $this->entityManager->persist($joueur);
        $this->entityManager->flush();
        $this->client->loginUser($joueur);

        $this->client->request('GET', '/ma-collection');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('a[href="/wiki/'.$stickman->getSlug().'"][class~="stickman-card-link--non-obtenue"]');
    }

    /** @return array{CollectionJeu, Stickman} */
    private function creerCollectionEtStickman(): array
    {
        $suffixe = bin2hex(random_bytes(5));
        $collection = (new CollectionJeu())->setNom('Collection cartes '.$suffixe)->setSlug('collection-cartes-'.$suffixe)->setDescription('Collection de validation des couleurs.')->setSaison(91)->setStatutActif(true)->setDateDebut(new \DateTimeImmutable('-1 day'))->setDateFin(new \DateTimeImmutable('+1 day'));
        $stickman = (new Stickman())->setNom('Carte couleur '.$suffixe)->setSlug('carte-couleur-'.$suffixe)->setDescription('Carte utilisée pour vérifier le grisage.')->setImage('carte-couleur.png')->setRarete(4)->setPv(20)->setAttaque(8)->setDefense(6)->setStatutActif(true)->setCollectionJeu($collection);
        $this->entityManager->persist($collection);
        $this->entityManager->persist($stickman);
        $this->entityManager->flush();
        $this->entityManager->refresh($collection);

        return [$collection, $stickman];
    }
}
