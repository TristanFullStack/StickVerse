<?php

namespace App\Service;

use App\Entity\Caisse;
use App\Entity\CaissePossedee;
use App\Entity\User;
use App\Repository\CaissePossedeeRepository;
use Doctrine\ORM\EntityManagerInterface;

final class InventaireCaisseService
{
    public function __construct(
        private readonly CaissePossedeeRepository $repository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** @return list<CaissePossedee> */
    public function lister(User $joueur): array
    {
        return $this->repository->trouverPourJoueur($joueur);
    }

    public function ajouter(User $joueur, Caisse $caisse, int $quantite = 1): void
    {
        for ($i = 0; $i < max(0, $quantite); ++$i) {
            $possession = (new CaissePossedee($joueur, $caisse));
            $joueur->addCaissePossedee($possession);
            $this->entityManager->persist($possession);
        }
    }

    public function consommer(User $joueur, Caisse $caisse): bool
    {
        $possession = $this->repository->trouverPremierePourJoueurEtCaisse($joueur, $caisse);
        if (!$possession instanceof CaissePossedee) {
            return false;
        }

        $this->entityManager->remove($possession);
        $joueur->removeCaissePossedee($possession);

        return true;
    }

    public function compter(User $joueur): int
    {
        return $this->repository->compterPourJoueur($joueur);
    }

    public function compterPourCaisse(User $joueur, Caisse $caisse): int
    {
        return $this->repository->compterPourJoueurEtCaisse($joueur, $caisse);
    }
}
