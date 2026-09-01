<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\InitialisationNouveauJoueurService;
use PHPUnit\Framework\TestCase;

final class InitialisationNouveauJoueurServiceTest extends TestCase
{
    public function testAttribueCinqCaissesOffertesSansCarteDeDepart(): void
    {
        $utilisateur = (new User())
            ->setEmail('nouveau@example.com')
            ->setPassword('mot-de-passe-test');

        (new InitialisationNouveauJoueurService())->initialiser($utilisateur);

        self::assertSame(5, $utilisateur->getCaissesPremiersRenforts());
        self::assertCount(0, $utilisateur->getInventaires());
    }

    public function testReinitialiseLeNombreDeCaissesOffertes(): void
    {
        $utilisateur = (new User())
            ->setCaissesPremiersRenforts(0);

        (new InitialisationNouveauJoueurService())->initialiser($utilisateur);

        self::assertSame(5, $utilisateur->getCaissesPremiersRenforts());
    }
}
