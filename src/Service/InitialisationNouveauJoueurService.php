<?php

namespace App\Service;

use App\Entity\Caisse;
use App\Entity\User;
use App\Repository\CaisseRepository;
use Doctrine\ORM\EntityManagerInterface;
use DateTimeImmutable;

final class InitialisationNouveauJoueurService
{
    public function __construct(
        private readonly ?CaisseRepository $caisseRepository = null,
        private readonly ?EntityManagerInterface $entityManager = null,
    ) {
    }

    /**
     * Prépare le compte sans lui attribuer de Stickman de base.
     * Les cinq caisses Premiers Renforts sont directement disponibles,
     * tandis que les pièces de départ restent utilisables pour des ouvertures
     * payantes.
     */
    public function initialiser(User $utilisateur): void
    {
        $utilisateur->setCaissesPremiersRenforts(
            User::CAISSES_PREMIERS_RENFORTS_DEPART,
        )->setDateDerniereRecompenseHoraire(new DateTimeImmutable());

        // Les comptes créés par l’application utilisent désormais de vraies
        // lignes d’inventaire. Le compteur historique reste utilisé par les
        // anciens tests/comptes qui ne disposent pas encore de cette table.
        $caisse = $this->caisseRepository?->findOneBy([
            'slug' => Caisse::SLUG_PREMIERS_RENFORTS,
        ]);
        if ($caisse === null || $this->entityManager === null) {
            return;
        }

        $utilisateur->setCaissesPremiersRenforts(0);
        for ($i = 0; $i < User::CAISSES_PREMIERS_RENFORTS_DEPART; ++$i) {
            $possession = new \App\Entity\CaissePossedee($utilisateur, $caisse);
            $utilisateur->addCaissePossedee($possession);
            $this->entityManager->persist($possession);
        }
    }
}
