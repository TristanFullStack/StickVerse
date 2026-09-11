<?php

namespace App\Tests\Controller;

use App\Entity\{User, Stickman, CollectionJeu, Inventaire, Equipe, Caisse, CaisseStickman, Actualite, Combat, CombattantCombat};
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Response / design-contract regression tests.
 * Optional HTML exports are generated from test fixtures only; all DB changes roll back.
 */
final class VisualIdentityControllerTest extends WebTestCase
{
    public function testPublicPlayerAndAdminPagesShareTheAccessibleShell(): void
    {
        $client = static::createClient();
        $client->disableReboot();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $db = $em->getConnection();
        self::assertStringEndsWith('_test', $db->fetchOne('SELECT DATABASE()'));
        $db->beginTransaction();
        try {
            $suffix = bin2hex(random_bytes(5));
            $collection = (new CollectionJeu())->setNom('Collection Origine')->setSlug('audit-'.$suffix)
                ->setDescription('Les premières formations de StickVerse.')->setSaison(97)
                ->setStatutActif(true)->setDateDebut(new \DateTimeImmutable('-1 day'))->setDateFin(new \DateTimeImmutable('+1 day'));
            $em->persist($collection);
            $cards = [];
            foreach (['Recrue' => '11-Recrue.png', 'Garde' => '14-Garde.png', 'Fantassin' => '12-Fantassin.png', 'Guerrier' => '13-Guerrier.png'] as $name => $image) {
                $card = (new Stickman())->setNom($name)->setSlug('audit-'.strtolower($name).'-'.$suffix)
                    ->setDescription('Un combattant de la collection Origine. Combine ses forces avec celles de ton équipe.')
                    ->setImage($image)->setRarete(count($cards) + 1)->setPv(60)->setAttaque(12)->setDefense(16)
                    ->setStatutActif(true)->setCollectionJeu($collection)
                    ->setPassifs([['nom' => 'Esprit d’équipe', 'description' => str_repeat('Protège ses alliés grâce à une formation coordonnée. ', 12)]]);
                $em->persist($card);
                $cards[] = $card;
            }
            $caisse = (new Caisse())->setNom('Caisse Origine')->setSlug('audit-caisse-'.$suffix)
                ->setDescription('Découvre les Stickmans de la collection Origine.')->setImage('caisse-commune.png')
                ->setPrix(500)->setStatutActif(true)->setCollectionJeu($collection);
            $em->persist($caisse);
            foreach ($cards as $card) {
                $content = (new CaisseStickman())->setCaisse($caisse)->setStickman($card)->setPoids(25);
                $em->persist($content);
            }
            $user = (new User())->setEmail('audit-'.$suffix.'@example.com')->setPseudo('Explorateur')
                ->setPassword('not-a-login-password')->setEmailVerifie(true)->setPieces(2000);
            $em->persist($user);
            foreach ($cards as $card) $em->persist((new Inventaire())->setUtilisateur($user)->setStickman($card)->setQuantite(2));
            $team = (new Equipe())->setNom('Les éclaireurs')->setUtilisateur($user)
                ->setStickmanA($cards[0])->setStickmanB($cards[1])->setStickmanC($cards[2])->setStickmanD($cards[3]);
            $em->persist($team);
            $em->flush();
            $em->refresh($collection);
            $em->refresh($caisse);
            $routes = static::getContainer()->get('router');
            $export = function (string $name) use ($client): void {
                if (!getenv('STICKVERSE_VISUAL_EXPORT')) return;
                $directory = dirname(__DIR__, 2).'/var/ui-audit';
                if (!is_dir($directory)) mkdir($directory, 0777, true);
                file_put_contents($directory.'/'.$name.'.html', $client->getResponse()->getContent());
            };
            $visit = function (string $name, string $route, array $params = []) use ($client, $routes, $export): void {
                $client->request('GET', $routes->generate($route, $params));
                self::assertResponseIsSuccessful($name);
                self::assertSelectorExists('html[lang="fr"]');
                self::assertSelectorCount(1, 'main');
                self::assertSelectorExists('.skip-link[href="#main-content"]');
                self::assertSelectorExists('button[data-navigation-toggle][aria-controls="main-navigation"]');
                self::assertSelectorExists('link[href*="design-system"]');
                $export($name);
            };
            foreach (['home' => 'app_home', 'wiki' => 'app_wiki', 'shop' => 'app_caisse_publique', 'news-empty' => 'app_actualite_index', 'login' => 'app_login', 'register' => 'app_register', 'password-forgotten' => 'app_demande_reinitialisation_mot_de_passe'] as $name => $route) $visit($name, $route);
            $visit('card', 'app_wiki_show', ['slug' => $cards[0]->getSlug()]);
            self::assertSelectorTextContains('.collectible-stats', '60');
            self::assertSelectorExists('.collectible button[data-passif-viewer-trigger]');
            self::assertSelectorNotExists('.collectible a button');
            $visit('crate', 'app_caisse_publique_show', ['id' => $caisse->getId()]);
            self::assertSelectorCount(4, '.crate-rarities span');
            $client->loginUser($user);
            foreach (['home-player' => 'app_home', 'inventory' => 'app_inventaire', 'collection' => 'app_collection', 'teams' => 'app_equipe', 'profile' => 'app_profil', 'password-change' => 'app_modifier_mot_de_passe', 'pseudo-change' => 'app_modifier_pseudo', 'account-delete' => 'app_supprimer_compte', 'rewards' => 'app_recompenses', 'season' => 'app_saison', 'ranking' => 'app_classement', 'combat' => 'app_combats_en_ligne', 'shop-player' => 'app_caisse_publique'] as $name => $route) $visit($name, $route);
            self::assertSelectorTextContains('#caisse-audit-caisse-'.$suffix.' .crate-collection-progress', '4 / 4');
            $client->request('GET', '/inventaire?vente=1');
            self::assertResponseIsSuccessful();
            $export('inventory-sale');
            // Capture the lobby's actual read-only JSON for browser fixtures.
            if (getenv('STICKVERSE_VISUAL_EXPORT')) {
                $client->request('GET', $routes->generate('app_salon_combat_en_ligne_etat'));
                file_put_contents(dirname(__DIR__, 2).'/var/ui-audit/lobby.json', $client->getResponse()->getContent());
            }
            // Lobby refresh services may clear the EntityManager; reacquire fixtures.
            $user = $em->find(User::class, $user->getId());
            $cards = array_map(fn (Stickman $card) => $em->find(Stickman::class, $card->getId()), $cards);
            $opponent = (new User())->setEmail('opponent-'.$suffix.'@example.com')->setPassword('test')->setPseudo('Sentinelle')->setEmailVerifie(true);
            $em->persist($opponent);
            $em->flush();
            $battle = (new Combat($user))->setJoueur2($opponent)->setStatut(Combat::STATUT_EN_COURS);
            $em->persist($battle);
            foreach (['A', 'B', 'C', 'D'] as $index => $slot) {
                $em->persist(new CombattantCombat($battle, $user, $slot, $cards[$index]));
                $em->persist(new CombattantCombat($battle, $opponent, $slot, $cards[$index]));
            }
            $em->flush();
            if (getenv('STICKVERSE_VISUAL_EXPORT')) {
                $client->request('GET', $routes->generate('app_salon_combat_en_ligne_etat'));
                file_put_contents(dirname(__DIR__, 2).'/var/ui-audit/lobby-active.json', $client->getResponse()->getContent());
                $client->request('GET', $routes->generate('app_combat_en_ligne_etat', ['id' => $battle->getId()]));
                self::assertResponseIsSuccessful();
                file_put_contents(dirname(__DIR__, 2).'/var/ui-audit/battle.json', $client->getResponse()->getContent());
            }
            $news = (new Actualite())->setTitre('Une nouvelle aventure commence')->setSlug('audit-news-'.$suffix)
                ->setContenu('Article de test visuel, jamais enregistré en production.')->setDatePublication(new \DateTimeImmutable('-1 hour'))->setStatutActif(true);
            $em->persist($news);
            $user = $em->find(User::class, $user->getId());
            $user->setRoles(['ROLE_ADMIN']);
            $em->flush();
            $client->loginUser($user);
            $visit('news', 'app_actualite_index');
            $visit('article', 'app_actualite_show', ['slug' => $news->getSlug()]);
            foreach (['admin' => 'app_admin_console', 'admin-cards' => 'app_stickman_index', 'admin-card-form' => 'app_stickman_new', 'admin-news' => 'app_actualite_admin_index'] as $name => $route) $visit($name, $route);
        } finally {
            if ($db->isTransactionActive()) $db->rollBack();
            $em->clear();
        }
    }
}
