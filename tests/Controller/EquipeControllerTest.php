<?php

namespace App\Tests\Controller;

use App\Entity\Equipe;
use App\Entity\Inventaire;
use App\Entity\Stickman;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EquipeControllerTest extends WebTestCase
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
        $this->client->request('GET', '/equipe');

        self::assertResponseRedirects('/login');
    }

    public function testCreeModifieEtSupprimePlusieursEquipes(): void
    {
        [$joueur, $stickmen] = $this->creerJoueurEtCollection();
        $premiereEquipe = $this->creerEquipe(
            $joueur,
            $stickmen,
            'Équipe principale',
        );
        $secondeEquipe = $this->creerEquipe(
            $joueur,
            array_reverse($stickmen),
            'Équipe débutant',
        );
        $this->entityManager->persist($premiereEquipe);
        $this->entityManager->persist($secondeEquipe);
        $this->entityManager->flush();

        $this->client->loginUser($joueur);
        $crawler = $this->client->request('GET', '/equipe');

        self::assertResponseIsSuccessful();
        self::assertSelectorCount(2, '.team-saved-card');
        self::assertSelectorTextContains('.team-saved-list', 'Puissance 300');

        $form = $crawler->selectButton('Créer cette équipe')->form([
            'equipe[nom]' => 'Équipe test',
            'equipe[stickmanA]' => (string) $stickmen[0]->getId(),
            'equipe[stickmanB]' => (string) $stickmen[1]->getId(),
            'equipe[stickmanC]' => (string) $stickmen[2]->getId(),
            'equipe[stickmanD]' => (string) $stickmen[3]->getId(),
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/equipe');
        $equipeCreee = $this->entityManager
            ->getRepository(Equipe::class)
            ->findOneBy(['utilisateur' => $joueur, 'nom' => 'Équipe test']);
        self::assertInstanceOf(Equipe::class, $equipeCreee);

        $crawler = $this->client->request(
            'GET',
            '/equipe/'.$equipeCreee->getId().'/modifier',
        );
        self::assertResponseIsSuccessful();
        $form = $crawler->selectButton('Enregistrer les modifications')->form([
            'equipe[nom]' => 'Équipe test modifiée',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/equipe');
        $equipeCreeeId = $equipeCreee->getId();
        self::assertNotNull($equipeCreeeId);
        $this->entityManager->clear();
        $equipeModifiee = $this->entityManager
            ->getRepository(Equipe::class)
            ->find($equipeCreeeId);
        self::assertInstanceOf(Equipe::class, $equipeModifiee);
        self::assertSame('Équipe test modifiée', $equipeModifiee->getNom());

        $crawler = $this->client->request('GET', '/equipe');
        $formulaireSuppression = $crawler->filter(
            'form[action="/equipe/'.$equipeCreeeId.'/supprimer"]'
        );
        self::assertCount(1, $formulaireSuppression);
        $jeton = $formulaireSuppression->filter('input[name="_token"]')->attr('value');
        self::assertIsString($jeton);

        $this->client->request(
            'POST',
            '/equipe/'.$equipeCreeeId.'/supprimer',
            ['_token' => $jeton],
        );

        self::assertResponseRedirects('/equipe');
        $this->entityManager->clear();
        self::assertNull(
            $this->entityManager->getRepository(Equipe::class)->find($equipeCreeeId)
        );
    }

    public function testRefuseLaModificationDUneEquipeDUnAutreJoueur(): void
    {
        [$proprietaire, $stickmen] = $this->creerJoueurEtCollection();
        $equipe = $this->creerEquipe(
            $proprietaire,
            $stickmen,
            'Équipe privée',
        );
        $autreJoueur = (new User())
            ->setEmail('autre-equipe-'.bin2hex(random_bytes(6)).'@example.com')
            ->setPassword('mot-de-passe-test');
        $this->entityManager->persist($equipe);
        $this->entityManager->persist($autreJoueur);
        $this->entityManager->flush();

        $this->client->loginUser($autreJoueur);
        $this->client->request(
            'GET',
            '/equipe/'.$equipe->getId().'/modifier',
        );

        self::assertResponseStatusCodeSame(403);
    }

    /**
     * @return array{User, list<Stickman>}
     */
    private function creerJoueurEtCollection(): array
    {
        $suffixe = bin2hex(random_bytes(6));
        $joueur = (new User())
            ->setEmail('equipes-'.$suffixe.'@example.com')
            ->setPassword('mot-de-passe-test');
        $this->entityManager->persist($joueur);
        $stickmen = [];

        foreach (range(1, 4) as $numero) {
            $stickman = (new Stickman())
                ->setNom('Stickman équipe '.$numero)
                ->setSlug('stickman-equipe-'.$suffixe.'-'.$numero)
                ->setDescription('Stickman utilisé pour tester les équipes.')
                ->setImage('stickman-equipe-'.$numero.'.png')
                ->setRarete(1)
                ->setPv(100)
                ->setAttaque(20)
                ->setDefense(10)
                ->setStatutActif(true);
            $inventaire = (new Inventaire())
                ->setUtilisateur($joueur)
                ->setStickman($stickman)
                ->setQuantite(1);
            $this->entityManager->persist($stickman);
            $this->entityManager->persist($inventaire);
            $stickmen[] = $stickman;
        }

        $this->entityManager->flush();

        return [$joueur, $stickmen];
    }

    /**
     * @param list<Stickman> $stickmen
     */
    private function creerEquipe(
        User $joueur,
        array $stickmen,
        string $nom,
    ): Equipe {
        return (new Equipe())
            ->setUtilisateur($joueur)
            ->setNom($nom)
            ->setStickmanA($stickmen[0])
            ->setStickmanB($stickmen[1])
            ->setStickmanC($stickmen[2])
            ->setStickmanD($stickmen[3]);
    }
}
