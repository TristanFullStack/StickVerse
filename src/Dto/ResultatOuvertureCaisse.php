<?php

namespace App\Dto;

use App\Entity\Caisse;
use App\Entity\Stickman;

final readonly class ResultatOuvertureCaisse
{
    /** @param list<Stickman> $stickmenDisponibles */
    public function __construct(
        public int $ouvertureId,
        public Caisse $caisse,
        public Stickman $stickman,
        public array $stickmenDisponibles,
        public int $quantiteApres,
        public bool $nouveau,
        public int $collectionPossedes,
        public int $collectionTotal,
        public int $soldePieces,
        public int $caissesOffertesRestantes,
        public bool $peutOuvrirEncore,
        public bool $rejouee = false,
    ) {
    }
}
