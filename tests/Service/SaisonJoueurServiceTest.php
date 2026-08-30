<?php

namespace App\Tests\Service;

use App\Entity\Caisse;
use App\Entity\CollectionJeu;
use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\CollectionJeuRepository;
use App\Repository\InventaireRepository;
use App\Service\SaisonJoueurService;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SaisonJoueurServiceTest extends TestCase
{
    public function testConstruitLaSaisonActiveDuJoueur(): void
    {
        $date = new DateTimeImmutable('2026-08-30 12:00:00');
        $joueur = (new User())->setEmail('saison@example.com');
        $collection = (new CollectionJeu())
            ->setNom('Premiers Renforts')
            ->setSlug('premiers-renforts')
            ->setDescription('Saison de test')
            ->setSaison(1)
            ->setStatutActif(true);
        $stickman = (new Stickman())
            ->setNom('Recrue')->setSlug('recrue')->setDescription('Recrue')
            ->setImage('recrue.png')->setRarete(1)->setPv(60)
            ->setAttaque(12)->setDefense(14)->setStatutActif(true);
        $caisse = (new Caisse())->setNom('Caisse Saison 1')->setStatutActif(true);
        $collection->getStickmen()->add($stickman);
        $collection->getCaisses()->add($caisse);

        $collections = $this->createMock(CollectionJeuRepository::class);
        $inventaires = $this->createMock(InventaireRepository::class);
        $collections->expects(self::once())->method('trouverSaisonActive')->with($date)->willReturn($collection);
        $inventaires->expects(self::once())->method('findBy')->with(['utilisateur' => $joueur])->willReturn([]);

        $saison = (new SaisonJoueurService($collections, $inventaires))->construire($joueur, $date);

        self::assertNotNull($saison);
        self::assertSame($collection, $saison['collection']);
        self::assertSame([$stickman], $saison['stickmen']);
        self::assertSame([$caisse], $saison['caisses']);
        self::assertSame(0, $saison['possedes']);
        self::assertSame(1, $saison['total']);
        self::assertSame(0, $saison['pourcentage']);
    }

    public function testRetourneNullSansSaisonActive(): void
    {
        $joueur = (new User())->setEmail('sans-saison@example.com');
        $collections = $this->createMock(CollectionJeuRepository::class);
        $inventaires = $this->createStub(InventaireRepository::class);
        $collections->expects(self::once())->method('trouverSaisonActive')->willReturn(null);

        self::assertNull((new SaisonJoueurService($collections, $inventaires))->construire($joueur));
    }
}
