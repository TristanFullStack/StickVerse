<?php

namespace App\Service;

use App\Entity\Caisse;
use App\Entity\Stickman;
use App\Entity\Inventaire;
use App\Entity\User;

use App\Repository\InventaireRepository;
use Doctrine\ORM\EntityManagerInterface;

final class OuvertureCaisseService
{
    public function __construct(
        private readonly InventaireRepository $inventaireRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function ouvrir(Caisse $caisse, User $utilisateur): ?Stickman
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
                $stickman = $contenu->getStickman();

                if ($stickman === null) {
                    return null;
                }

                $this->ajouterDansInventaire($utilisateur, $stickman);

                return $stickman;
            }
        }

        return null;
    }
    
    private function ajouterDansInventaire(User $utilisateur, Stickman $stickman): void
    {
        $inventaire = $this->inventaireRepository->findOneBy([
            'utilisateur' => $utilisateur,
            'stickman' => $stickman,
        ]);

        if ($inventaire === null) {
            $inventaire = new Inventaire();
            $inventaire->setUtilisateur($utilisateur);
            $inventaire->setStickman($stickman);

            $this->entityManager->persist($inventaire);
        } else {
            $inventaire->setQuantite(
                ($inventaire->getQuantite() ?? 0) + 1
            );
        }

        $this->entityManager->flush();
    }

}