<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\CollectionJeuRepository;
use App\Repository\InventaireRepository;

final class CollectionJoueurService
{
    public function __construct(
        private readonly CollectionJeuRepository $collectionRepository,
        private readonly InventaireRepository $inventaireRepository,
        private readonly ?ScorePuissanceService $scorePuissanceService = null,
    ) {
    }

    /**
     * @return array<int, array{collection: object, stickmen: array, inventaires: array, puissances: array, possedes: int, total: int, pourcentage: int}>
     */
    public function construire(User $joueur): array
    {
        $inventaires = $this->inventaireRepository->findBy(['utilisateur' => $joueur]);
        $inventairesParStickman = [];

        foreach ($inventaires as $inventaire) {
            $stickman = $inventaire->getStickman();
            if ($stickman?->getId() !== null) {
                $inventairesParStickman[$stickman->getId()] = $inventaire;
            }
        }

        $resultat = [];
        foreach ($this->collectionRepository->trouverDisponibles() as $collection) {
            $stickmen = $collection->getStickmen()->toArray();
            if ($this->scorePuissanceService !== null) {
                $stickmen = $this->scorePuissanceService->trierStickmen($stickmen);
            } else {
                usort($stickmen, static fn ($premier, $second): int => strcasecmp(
                    $premier->getNom() ?? '',
                    $second->getNom() ?? '',
                ));
            }

            $inventairesCollection = [];
            $puissances = [];
            foreach ($stickmen as $stickman) {
                if ($stickman->getId() !== null && isset($inventairesParStickman[$stickman->getId()])) {
                    $inventairesCollection[$stickman->getId()] = $inventairesParStickman[$stickman->getId()];
                }
                if ($stickman->getId() !== null && $this->scorePuissanceService !== null) {
                    $puissances[$stickman->getId()] = $this->scorePuissanceService->calculerStickman($stickman);
                }
            }

            $total = count($stickmen);
            $possedes = count($inventairesCollection);
            $resultat[] = [
                'collection' => $collection,
                'stickmen' => $stickmen,
                'inventaires' => $inventairesCollection,
                'puissances' => $puissances,
                'possedes' => $possedes,
                'total' => $total,
                'pourcentage' => $total > 0 ? (int) round(($possedes / $total) * 100) : 0,
            ];
        }

        return $resultat;
    }
}
