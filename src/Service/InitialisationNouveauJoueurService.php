<?php

namespace App\Service;

use App\Entity\User;
use DateTimeImmutable;

final class InitialisationNouveauJoueurService
{
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
    }
}
