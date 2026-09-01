<?php

namespace App\Tests\Entity;

use App\Entity\ClassementSaisonJoueur;
use App\Entity\CollectionJeu;
use App\Entity\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ClassementSaisonJoueurTest extends TestCase
{
    public function testEnregistreLesResultatsEtLElo(): void
    {
        $classement = new ClassementSaisonJoueur(
            new User(),
            $this->creerSaison(),
        );

        $classement->enregistrerResultat(16, 1.0);
        $classement->enregistrerResultat(-5, 0.5);
        $classement->enregistrerResultat(-10, 0.0);

        self::assertSame(1001, $classement->getElo());
        self::assertSame(3, $classement->getParties());
        self::assertSame(1, $classement->getVictoires());
        self::assertSame(1, $classement->getDefaites());
        self::assertSame(1, $classement->getMatchsNuls());
    }

    public function testRefuseUneCollectionNonSaisonniere(): void
    {
        $collection = $this->creerSaison()->setSaison(0);

        $this->expectException(InvalidArgumentException::class);

        new ClassementSaisonJoueur(new User(), $collection);
    }

    private function creerSaison(): CollectionJeu
    {
        return (new CollectionJeu())
            ->setNom('Saison test')
            ->setSlug('saison-test')
            ->setDescription('Saison utilisée par les tests.')
            ->setSaison(1);
    }
}
