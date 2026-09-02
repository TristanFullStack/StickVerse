<?php

namespace App\Service;

use App\Entity\Caisse;
use App\Entity\CaisseStickman;
use App\Entity\Stickman;
use App\Exception\OuvertureCaisseImpossibleException;
use DateTimeImmutable;

final class TirageCaisseService
{
    /** @return list<CaisseStickman> */
    public function contenusEligibles(Caisse $caisse): array
    {
        $contenus = [];
        $maintenant = new DateTimeImmutable();
        $collectionCaisse = $caisse->getCollectionJeu();

        foreach ($caisse->getContenus() as $contenu) {
            $stickman = $contenu->getStickman();
            $collectionStickman = $stickman?->getCollectionJeu();
            $collectionCompatible = $collectionCaisse === null
                || $collectionStickman?->getId() === $collectionCaisse->getId();
            $collectionDisponible = $collectionStickman === null
                || $collectionStickman->estDisponibleA($maintenant);

            if (
                $stickman instanceof Stickman
                && $stickman->isStatutActif()
                && $collectionCompatible
                && $collectionDisponible
                && ($contenu->getPoids() ?? 0) > 0
            ) {
                $contenus[] = $contenu;
            }
        }

        return $contenus;
    }

    /** @param list<CaisseStickman>|null $contenus */
    public function tirer(Caisse $caisse, ?array $contenus = null): Stickman
    {
        $contenus ??= $this->contenusEligibles($caisse);
        $poidsTotal = array_sum(array_map(
            static fn (CaisseStickman $contenu): int => max(0, $contenu->getPoids() ?? 0),
            $contenus,
        ));

        if ($poidsTotal <= 0) {
            throw new OuvertureCaisseImpossibleException(
                'Cette caisse ne contient aucun Stickman disponible.'
            );
        }

        $numeroTire = random_int(1, $poidsTotal);
        $poidsCumule = 0;

        foreach ($contenus as $contenu) {
            $poidsCumule += max(0, $contenu->getPoids() ?? 0);

            if ($numeroTire <= $poidsCumule) {
                $stickman = $contenu->getStickman();

                if ($stickman instanceof Stickman) {
                    return $stickman;
                }
            }
        }

        throw new OuvertureCaisseImpossibleException(
            'Le tirage de cette caisse est invalide.'
        );
    }
}
