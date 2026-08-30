<?php

namespace App\Tests\Controller;

use App\Entity\Combat;
use App\Entity\ResultatRoundCombat;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class InterfaceCombatEnLigneControllerTest extends WebTestCase
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

        self::assertInstanceOf(
            EntityManagerInterface::class,
            $entityManager,
        );

        $this->entityManager = $entityManager;

        $connexion = $this->entityManager->getConnection();
        $nomBase = $connexion->fetchOne('SELECT DATABASE()');

        if (
            !is_string($nomBase)
            || !str_ends_with($nomBase, '_test')
        ) {
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

    public function testRedirigeUnVisiteurAnonymeVersLaConnexion(): void
    {
        $this->client->request('GET', '/combats');

        self::assertResponseRedirects('/login');
    }

    public function testAfficheLaRacineDeLInterfaceAuJoueurConnecte(): void
    {
        $suffixe = bin2hex(random_bytes(6));

        $joueur = (new User())
            ->setEmail(
                'interface-combat-'.$suffixe.'@example.com'
            )
            ->setPassword('mot-de-passe-test');

        $this->entityManager->persist($joueur);
        $this->entityManager->flush();

        $this->client->loginUser($joueur);
        $this->client->request('GET', '/combats');

        self::assertResponseIsSuccessful();
        $this->assertReponseNonMiseEnCache();
        self::assertSelectorTextContains(
            'h1',
            'Combats en ligne',
        );
        self::assertSelectorExists(
            '#combat-en-ligne[data-controller="combat-en-ligne"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="information"][role="status"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="reessayerButton"]'
            .'[data-action="combat-en-ligne#rafraichir"]'
        );
        self::assertSelectorExists(
            'link[rel="stylesheet"][data-combat-en-ligne-style]'
        );
        self::assertSelectorExists(
            '#combat-en-ligne[data-combat-en-ligne-salon-url-value="/salon-combat-en-ligne"]'
        );
        self::assertSelectorExists(
            '#combat-en-ligne[data-combat-en-ligne-creer-url-value="/salon-combat-en-ligne/creer"]'
        );
        self::assertSelectorExists(
            '#combat-en-ligne[data-combat-en-ligne-rejoindre-url-modele-value="/salon-combat-en-ligne/__combat_id__/rejoindre"]'
        );
        self::assertSelectorExists(
            '#combat-en-ligne[data-combat-en-ligne-rejoindre-par-code-url-value="/salon-combat-en-ligne/rejoindre-par-code"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="equipeSelect"]'
        );
        self::assertSelectorExists(
            '[data-action="combat-en-ligne#creerCombat"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="combatPrive"][type="checkbox"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="combatsDisponibles"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="aucunHistoriqueCombat"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="historiqueCombats"]'
        );
        self::assertSelectorTextContains(
            '#historique-combats-titre',
            'Mes derniers combats',
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="combatActif"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="combatStatut"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="numeroRound"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="finCombat"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="finCombatTitre"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="finCombatMessage"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="rapportFinalLink"]'
        );
        self::assertSelectorExists(
            '[data-action="combat-en-ligne#retournerSalon"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="attenteAdversaire"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="invitationCombat"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="invitationCode"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="visibiliteCombat"]'
        );
        self::assertSelectorExists(
            '[data-action="combat-en-ligne#copierCodeInvitation"]'
        );
        self::assertSelectorExists(
            'form[data-action="submit->combat-en-ligne#rejoindreParCode"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="codeInvitationInput"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="moiCombattants"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="adversaireCombattants"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="resultatRound"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="resultatRoundNumero"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="resultatRoundLignes"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="historiqueRounds"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="historiqueRoundsListe"]'
        );
        self::assertSelectorTextContains(
            '#historique-rounds-titre',
            'Rounds précédents',
        );
        self::assertSelectorExists(
            '#combat-en-ligne[data-combat-en-ligne-plan-url-modele-value="/combat-en-ligne/__combat_id__/plan"]'
        );
        self::assertSelectorExists(
            '#combat-en-ligne[data-combat-en-ligne-pret-url-modele-value="/combat-en-ligne/__combat_id__/pret"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="preparationSection"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="preparationMessage"][role="status"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="pretButton"]'
            .'[data-action="combat-en-ligne#confirmerPret"]'
        );
        self::assertSelectorExists(
            '#combat-en-ligne[data-combat-en-ligne-abandon-url-modele-value="/combat-en-ligne/__combat_id__/abandon"]'
        );
        self::assertSelectorExists(
            '#combat-en-ligne[data-combat-en-ligne-annuler-url-modele-value="/combat-en-ligne/__combat_id__/annuler"]'
        );
        self::assertSelectorExists(
            '#combat-en-ligne[data-combat-en-ligne-rapport-url-modele-value="/combats/__combat_id__/rapport"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="planSection"]'
        );
        self::assertSelectorExists(
            'form[data-action="submit->combat-en-ligne#soumettrePlan"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="cibleAttaqueX"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="cibleDefenseX"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="cibleAttaqueY"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="cibleDefenseY"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="abandonButton"][data-action="combat-en-ligne#abandonnerCombat"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="annulerButton"][data-action="combat-en-ligne#annulerCombat"]'
        );
        self::assertSelectorTextContains('.site-account', $joueur->getEmail());
        self::assertSelectorExists('.site-name[href="/home"]');
        self::assertSelectorExists('.site-navigation a[href="/wiki"]');
        self::assertSelectorExists('.site-navigation a[href="/caisses"]');
        self::assertSelectorExists('.site-navigation a[href="/ma-collection"]');
        self::assertSelectorExists('.site-navigation a[href="/equipe"]');
        self::assertSelectorExists(
            '.site-navigation a[href="/combats"][aria-current="page"]',
        );
        self::assertSelectorExists('.site-account a[href="/logout"]');
        self::assertSelectorNotExists('[data-navigation-admin]');
    }

    public function testAfficheLesLiensAdministrationUniquementALAdministrateur(): void
    {
        $administrateur = (new User())
            ->setEmail(
                'navigation-admin-'
                .bin2hex(random_bytes(6))
                .'@example.com'
            )
            ->setPassword('mot-de-passe-test')
            ->setRoles(['ROLE_ADMIN']);

        $this->entityManager->persist($administrateur);
        $this->entityManager->flush();

        $this->client->loginUser($administrateur);
        $this->client->request('GET', '/combats');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(
            '[data-navigation-admin] a[href="/admin/stickman"]',
        );
        self::assertSelectorExists(
            '[data-navigation-admin] a[href="/admin/caisse"]',
        );
        self::assertSelectorExists(
            '[data-navigation-admin] a[href="/admin/caisse-stickman"]',
        );
    }

    private function assertReponseNonMiseEnCache(): void
    {
        $reponse = $this->client->getResponse();

        self::assertTrue(
            $reponse->headers->hasCacheControlDirective('private'),
        );
        self::assertTrue(
            $reponse->headers->hasCacheControlDirective('no-store'),
        );
        self::assertTrue(
            $reponse->headers->hasCacheControlDirective('no-cache'),
        );
        self::assertSame('no-cache', $reponse->headers->get('Pragma'));
    }

    public function testAfficheLeRapportSeulementAuxParticipants(): void
    {
        $suffixe = bin2hex(random_bytes(6));
        $joueur = (new User())
            ->setEmail('rapport-'.$suffixe.'@example.com')
            ->setPassword('mot-de-passe-test');
        $adversaire = (new User())
            ->setEmail('adversaire-rapport-'.$suffixe.'@example.com')
            ->setPassword('mot-de-passe-test');
        $exterieur = (new User())
            ->setEmail('exterieur-rapport-'.$suffixe.'@example.com')
            ->setPassword('mot-de-passe-test');

        $this->entityManager->persist($joueur);
        $this->entityManager->persist($adversaire);
        $this->entityManager->persist($exterieur);

        $combat = (new Combat($joueur))
            ->setJoueur2($adversaire)
            ->setStatut(Combat::STATUT_TERMINE)
            ->setGagnant($joueur)
            ->enregistrerResultatsRound(
                1,
                [
                    'joueur2_A' => [
                        'attaque' => 4,
                        'defense' => 1,
                        'degatsEffectifs' => 3,
                        'pvRestants' => 1,
                    ],
                ],
            );

        new ResultatRoundCombat(
            $combat,
            1,
            $combat->getDerniersResultats() ?? [],
        );

        $this->entityManager->persist($combat);
        $this->entityManager->flush();

        $combatId = $combat->getId();
        self::assertNotNull($combatId);

        $this->client->loginUser($joueur);
        $this->client->request(
            'GET',
            '/combats/'.$combatId.'/rapport',
        );

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Combat #'.$combatId);
        self::assertSelectorTextContains(
            '.rapport-resultat',
            'Victoire',
        );
        self::assertSelectorTextContains(
            '#rapport-combat > header',
            $adversaire->getEmail(),
        );
        self::assertSelectorTextContains(
            '#rapport-rounds-titre',
            'Déroulement du combat',
        );
        self::assertSelectorTextContains(
            '.rapport-round h3',
            'Round 1',
        );
        self::assertSelectorExists(
            'a[href="/combats"]',
        );

        $this->client->loginUser($exterieur);
        $this->client->request(
            'GET',
            '/combats/'.$combatId.'/rapport',
        );

        self::assertResponseStatusCodeSame(403);
    }
}
