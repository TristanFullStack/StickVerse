<?php

namespace App\Service;

use App\Entity\MouvementPieces;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

final class MouvementPiecesService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function enregistrer(
        User $joueur,
        int $montant,
        string $type,
        string $libelle,
    ): MouvementPieces {
        $mouvement = new MouvementPieces(
            $joueur,
            $montant,
            $type,
            $libelle,
        );

        $joueur->addMouvementPieces($mouvement);
        $this->entityManager->persist($mouvement);

        return $mouvement;
    }
}
