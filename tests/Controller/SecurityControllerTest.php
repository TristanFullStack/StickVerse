<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityControllerTest extends WebTestCase
{
    public function testPageConnexionEstEnFrancaisEtGuideLeJoueur(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1#connexion-titre', 'Se connecter');
        self::assertSelectorExists('form.auth-form[action="/login"]');
        self::assertSelectorExists('input#username[autocomplete="email"]');
        self::assertSelectorExists('input#password[autocomplete="current-password"]');
        self::assertSelectorExists(
            '[data-password-visibility] button.auth-password-toggle[data-password-visibility-toggle][aria-controls="password"][aria-label="Afficher le mot de passe"]',
        );
        self::assertSelectorTextContains('button.auth-submit', 'Se connecter');
        self::assertSelectorTextContains('body', 'Renvoyer l’e-mail de confirmation');
    }

    public function testPageInscriptionPresenteLesEtapesEtLesChampsSecurises(): void
    {
        $client = static::createClient();
        $client->request('GET', '/register');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1#inscription-titre', 'Créer ton compte');
        self::assertSelectorExists('input[name="registration_form[pseudo]"][maxlength="24"]');
        self::assertSelectorExists('input[name="registration_form[email]"][type="email"]');
        self::assertSelectorExists('input[name="registration_form[plainPassword]"][autocomplete="new-password"]');
        self::assertSelectorExists(
            '[data-password-visibility] button.auth-password-toggle[data-password-visibility-toggle][aria-controls="registration_form_plainPassword"][aria-label="Afficher le mot de passe"]',
        );
        self::assertSelectorTextContains('body', 'Confirme ton e-mail');
        self::assertSelectorTextContains('body', 'Le lien reçu est valable 24 heures.');
    }

    public function testNavigationPubliqueAfficheSeulementLesLiensPublics(): void
    {
        $client = static::createClient();
        $client->request('GET', '/home');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(
            '.site-navigation a[href="/home"][aria-current="page"]',
        );
        self::assertSelectorExists('.site-navigation a[href="/wiki"]');
        self::assertSelectorExists('.site-navigation a[href="/caisses"]');
        self::assertSelectorExists('.site-account a[href="/login"]');
        self::assertSelectorExists('.site-account a[href="/register"]');
        self::assertSelectorNotExists(
            '.site-navigation a[href="/ma-collection"]',
        );
        self::assertSelectorNotExists('.site-navigation a[href="/equipe"]');
        self::assertSelectorNotExists('.site-navigation a[href="/combats"]');
        self::assertSelectorNotExists('.site-account a[href="/profil"]');
        self::assertSelectorNotExists('[data-navigation-admin]');
    }

    public function testBloqueApresCinqConnexionsEchouees(): void
    {
        $client = static::createClient(
            [],
            [
                'REMOTE_ADDR' =>
                    '2001:db8::'.bin2hex(random_bytes(2)),
            ],
        );
        $email = 'connexion-j61-'
            .bin2hex(random_bytes(6))
            .'@example.com';

        for ($tentative = 1; $tentative <= 6; $tentative++) {
            $pageConnexion = $client->request('GET', '/login');
            self::assertResponseIsSuccessful();

            $client->submit(
                $pageConnexion
                    ->selectButton('Se connecter')
                    ->form([
                        '_username' => $email,
                        '_password' => 'mot-de-passe-incorrect',
                    ])
            );

            self::assertResponseRedirects('/login');
            $client->followRedirect();
            self::assertResponseIsSuccessful();
        }

        self::assertSelectorTextContains(
            '.auth-alert--error',
            'Plusieurs tentatives de connexion ont échoué',
        );
    }
}
