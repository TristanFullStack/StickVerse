<?php

namespace App\Tests\Controller;

use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\InventaireRepository;
use App\Repository\StickmanRepository;
use App\Repository\UserRepository;
use App\Service\InitialisationNouveauJoueurService;
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
        $this->garantirStickmansDeDepart();
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

    public function testInscriptionConnecteEtPrepareLePremierParcours(): void
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

        self::assertResponseRedirects('/ma-collection');
        $this->client->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains(
            '.flash-success',
            'Ton pack de départ est prêt',
        );
        self::assertSelectorTextContains(
            '#progression-joueur-titre',
            'Tes prochaines étapes',
        );
        self::assertSelectorTextContains(
            'body',
            'Composer ma première équipe',
        );

        $userRepository = static::getContainer()->get(UserRepository::class);
        $inventaireRepository = static::getContainer()->get(
            InventaireRepository::class
        );
        $utilisateur = $userRepository->findOneBy(['email' => $email]);

        self::assertInstanceOf(User::class, $utilisateur);
        self::assertSame('NouveauJoueur', $utilisateur->getPseudo());

        $inventaires = $inventaireRepository->findBy([
            'utilisateur' => $utilisateur,
        ]);

        self::assertCount(4, $inventaires);
        self::assertEqualsCanonicalizing(
            InitialisationNouveauJoueurService::STICKMANS_DEPART,
            array_map(
                static fn ($inventaire): ?string =>
                    $inventaire->getStickman()?->getSlug(),
                $inventaires,
            ),
        );

        $identifiantsParSlug = [];

        foreach ($inventaires as $inventaire) {
            $stickman = $inventaire->getStickman();

            self::assertInstanceOf(Stickman::class, $stickman);
            self::assertNotNull($stickman->getId());
            $identifiantsParSlug[(string) $stickman->getSlug()] =
                (string) $stickman->getId();
        }

        $this->client->request('GET', '/equipe');
        self::assertResponseIsSuccessful();
        self::assertSelectorNotExists('.flash-error');
        self::assertSelectorExists('form[name="equipe"]');

        $this->client->submitForm('Créer cette équipe', [
            'equipe[nom]' => 'Équipe de départ',
            'equipe[stickmanA]' => $identifiantsParSlug['guerrier'],
            'equipe[stickmanB]' => $identifiantsParSlug['archer'],
            'equipe[stickmanC]' => $identifiantsParSlug['lancier'],
            'equipe[stickmanD]' => $identifiantsParSlug['tank'],
        ]);

        self::assertResponseRedirects('/equipe');
        $this->client->followRedirect();
        self::assertSelectorTextContains(
            '.flash-success',
            'La nouvelle équipe a bien été créée',
        );
        self::assertSelectorTextContains(
            'body',
            'Accéder aux combats en ligne',
        );

        $this->client->request('GET', '/combats');
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Combats en ligne');
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

    private function garantirStickmansDeDepart(): void
    {
        $repository = static::getContainer()->get(StickmanRepository::class);

        foreach (InitialisationNouveauJoueurService::STICKMANS_DEPART as $slug) {
            if ($repository->findOneBy(['slug' => $slug]) instanceof Stickman) {
                continue;
            }

            $this->entityManager->persist(
                (new Stickman())
                    ->setSlug($slug)
                    ->setNom(ucfirst($slug))
                    ->setDescription('Stickman de départ pour le test.')
                    ->setImage($slug.'.png')
                    ->setRarete(1)
                    ->setPv(10)
                    ->setAttaque(2)
                    ->setDefense(2)
                    ->setStatutActif(true),
            );
        }

        $this->entityManager->flush();
    }
}
