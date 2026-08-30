<?php

namespace App\Tests\Service;

use App\Entity\CollectionJeu;
use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\CollectionJeuRepository;
use App\Repository\InventaireRepository;
use App\Service\CollectionJoueurService;
use PHPUnit\Framework\TestCase;

final class CollectionJoueurServiceTest extends TestCase
{
    public function testConstruitLaProgressionParCollection(): void
    {
        $joueur = (new User())->setEmail('collection@example.com');
        $collection = (new CollectionJeu())
            ->setNom('Saison 1')
            ->setSlug('saison-1')
            ->setDescription('Renforts')
            ->setSaison(1);
        $stickman = (new Stickman())
            ->setNom('Guerrier')
            ->setSlug('guerrier')
            ->setDescription('Un guerrier')
            ->setImage('guerrier.svg')
            ->setRarete(3)
            ->setPv(340)
            ->setAttaque(55)
            ->setDefense(70)
            ->setStatutActif(true)
            ->setCollectionJeu($collection);
        $collection->getStickmen()->add($stickman);

        $collections = $this->createMock(CollectionJeuRepository::class);
        $inventaires = $this->createMock(InventaireRepository::class);
        $collections->expects(self::once())->method('trouverDisponibles')->willReturn([$collection]);
        $inventaires->expects(self::once())->method('findBy')->with(['utilisateur' => $joueur])->willReturn([]);

        $resultat = (new CollectionJoueurService($collections, $inventaires))->construire($joueur);

        self::assertCount(1, $resultat);
        self::assertSame($collection, $resultat[0]['collection']);
        self::assertSame(1, $resultat[0]['total']);
        self::assertSame(0, $resultat[0]['possedes']);
        self::assertSame(0, $resultat[0]['pourcentage']);
    }

    public function testTrieLesStickmenParNom(): void
    {
        $joueur = (new User())->setEmail('tri@example.com');
        $collection = (new CollectionJeu())
            ->setNom('Origine')->setSlug('origine')->setDescription('Départ')->setSaison(0);
        $noms = ['Tank', 'Archer', 'Guerrier'];
        foreach ($noms as $nom) {
            $collection->getStickmen()->add((new Stickman())
                ->setNom($nom)->setSlug(strtolower($nom))->setDescription($nom)->setImage('x.svg')
                ->setRarete(1)->setPv(1)->setAttaque(1)->setDefense(1)->setStatutActif(true));
        }

        $collections = $this->createMock(CollectionJeuRepository::class);
        $inventaires = $this->createMock(InventaireRepository::class);
        $collections->expects(self::once())->method('trouverDisponibles')->willReturn([$collection]);
        $inventaires->expects(self::once())->method('findBy')->willReturn([]);

        $resultat = (new CollectionJoueurService($collections, $inventaires))->construire($joueur);

        self::assertSame(['Archer', 'Guerrier', 'Tank'], array_map(
            static fn (Stickman $stickman): string => $stickman->getNom(),
            $resultat[0]['stickmen'],
        ));
    }
}
