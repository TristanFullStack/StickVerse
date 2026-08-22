<?php

namespace App\Tests\Controller;

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
        self::assertSelectorTextContains(
            'h1',
            'Combats en ligne',
        );
        self::assertSelectorExists(
            '#combat-en-ligne[data-controller="combat-en-ligne"]'
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
            '[data-combat-en-ligne-target="equipeSelect"]'
        );
        self::assertSelectorExists(
            '[data-action="combat-en-ligne#creerCombat"]'
        );
        self::assertSelectorExists(
            '[data-combat-en-ligne-target="combatsDisponibles"]'
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
            '[data-combat-en-ligne-target="attenteAdversaire"]'
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
            '#combat-en-ligne[data-combat-en-ligne-plan-url-modele-value="/combat-en-ligne/__combat_id__/plan"]'
        );
        self::assertSelectorExists(
            '#combat-en-ligne[data-combat-en-ligne-abandon-url-modele-value="/combat-en-ligne/__combat_id__/abandon"]'
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
        self::assertSelectorTextContains(
            '#combat-en-ligne header p',
            $joueur->getEmail(),
        );
    }
}
