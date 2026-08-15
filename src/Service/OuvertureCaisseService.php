<?php

namespace App\Service;

use App\Entity\Caisse;
use App\Entity\Stickman;

final class OuvertureCaisseService
{
    public function ouvrir(Caisse $caisse): ?Stickman
    {
        $contenus = $caisse->getContenus();
        if ($contenus->isEmpty()) {
            return null;
        }

        $poidsTotal = $caisse->getPoidsTotal();

        if ($poidsTotal <= 0) {
            return null;
        }

        $numeroTire = random_int(1, $poidsTotal);

        $poidsCumule = 0;

        foreach ($contenus as $contenu) {
            $poidsCumule += $contenu->getPoids() ?? 0;

            if ($numeroTire <= $poidsCumule) {
                return $contenu->getStickman();
            }
        }

        return null;
    }
}