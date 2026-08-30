<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SecurityControllerTest extends WebTestCase
{
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
                    ->selectButton('Sign in')
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
            '.alert-danger',
            'Too many failed login attempts',
        );
    }
}
