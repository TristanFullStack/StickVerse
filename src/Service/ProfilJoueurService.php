<?php

namespace App\Service;

use App\Entity\Equipe;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Repository\EquipeRepository;
use App\Repository\InventaireRepository;
use App\Repository\MouvementPiecesRepository;

final readonly class ProfilJoueurService
{
    public function __construct(
        private InventaireRepository $inventaireRepository,
        private EquipeRepository $equipeRepository,
        private CombatRepository $combatRepository,
        private ?MouvementPiecesRepository $mouvementPiecesRepository = null,
    ) {
    }

    /**
     * @return array{
     *     nombreStickmen: int,
     *     equipe: Equipe|null,
     *     statistiques: array{
     *         total: int,
     *         victoires: int,
     *         defaites: int,
     *         matchsNuls: int
     *     },
     *     mouvementsPieces: list<\App\Entity\MouvementPieces>
     * }
     */
    public function construire(User $joueur): array
    {
        return [
            'nombreStickmen' => $this->inventaireRepository->count([
                'utilisateur' => $joueur,
            ]),
            'equipe' => $this->equipeRepository->findOneBy([
                'utilisateur' => $joueur,
            ]),
            'statistiques' => $this->combatRepository
                ->calculerStatistiquesPourJoueur($joueur),
            'mouvementsPieces' => $this->mouvementPiecesRepository?->trouverPourJoueur(
                $joueur,
            ) ?? [],
        ];
    }
}
