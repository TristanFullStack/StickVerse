<?php

namespace App\Service;

use App\Entity\Caisse;
use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\CollectionJeuRepository;
use App\Repository\InventaireRepository;
use DateTimeImmutable;

final readonly class SaisonJoueurService
{
    public function __construct(
        private CollectionJeuRepository $collectionRepository,
        private InventaireRepository $inventaireRepository,
    ) {
    }

    /**
     * @return array{
     *     collection: object,
     *     stickmen: list<Stickman>,
     *     inventaires: array<int, object>,
     *     caisses: list<Caisse>,
     *     possedes: int,
     *     total: int,
     *     pourcentage: int
     * }|null
     */
    public function construire(User $joueur, ?DateTimeImmutable $date = null): ?array
    {
        $collection = $this->collectionRepository->trouverSaisonActive($date);
        if ($collection === null) {
            return null;
        }

        $inventairesParStickman = [];
        foreach ($this->inventaireRepository->findBy(['utilisateur' => $joueur]) as $inventaire) {
            $stickman = $inventaire->getStickman();
            if ($stickman?->getId() !== null) {
                $inventairesParStickman[$stickman->getId()] = $inventaire;
            }
        }

        $stickmen = array_values(array_filter(
            $collection->getStickmen()->toArray(),
            static fn (Stickman $stickman): bool => $stickman->isStatutActif() === true,
        ));
        usort($stickmen, static fn (Stickman $premier, Stickman $second): int => strcasecmp(
            $premier->getNom() ?? '',
            $second->getNom() ?? '',
        ));

        $inventairesSaison = [];
        foreach ($stickmen as $stickman) {
            if ($stickman->getId() !== null && isset($inventairesParStickman[$stickman->getId()])) {
                $inventairesSaison[$stickman->getId()] = $inventairesParStickman[$stickman->getId()];
            }
        }

        $caisses = array_values(array_filter(
            $collection->getCaisses()->toArray(),
            static fn (Caisse $caisse): bool => $caisse->isStatutActif() === true,
        ));
        usort($caisses, static fn (Caisse $premiere, Caisse $seconde): int => strcasecmp(
            $premiere->getNom() ?? '',
            $seconde->getNom() ?? '',
        ));

        $total = count($stickmen);
        $possedes = count($inventairesSaison);

        return [
            'collection' => $collection,
            'stickmen' => $stickmen,
            'inventaires' => $inventairesSaison,
            'caisses' => $caisses,
            'possedes' => $possedes,
            'total' => $total,
            'pourcentage' => $total > 0 ? (int) round(($possedes / $total) * 100) : 0,
        ];
    }
}
