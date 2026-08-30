<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\Equipe;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Repository\EquipeRepository;
use App\Repository\InventaireRepository;

final readonly class TableauDeBordJoueurService
{
    public function __construct(
        private InventaireRepository $inventaireRepository,
        private EquipeRepository $equipeRepository,
        private CombatRepository $combatRepository,
    ) {
    }

    /**
     * @return array{
     *     nombreStickmen: int,
     *     equipe: Equipe|null,
     *     combatActif: Combat|null,
     *     derniersCombats: list<Combat>
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
            'combatActif' => $this->combatRepository
                ->trouverActifPourJoueur($joueur),
            'derniersCombats' => $this->combatRepository
                ->trouverHistoriquePourJoueur($joueur, 3),
        ];
    }
}
