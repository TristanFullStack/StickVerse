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
        private ?RecompenseQuotidienneService $recompenseQuotidienneService = null,
        private ?ObjectifJoueurService $objectifJoueurService = null,
    ) {
    }

    /**
     * @return array{
     *     nombreStickmen: int,
     *     elo: int,
     *     equipe: Equipe|null,
     *     statistiques: array{
     *         total: int,
     *         victoires: int,
     *         defaites: int,
     *         matchsNuls: int
     *     },
     *     mouvementsPieces: list<\App\Entity\MouvementPieces>
     *     recompenseQuotidienneDisponible: bool
     *     objectifs: list<array{id: string, libelle: string, description: string, progression: int, cible: int, recompense: int, reclame: bool, disponible: bool}>
     * }
     */
    public function construire(User $joueur): array
    {
        return [
            'nombreStickmen' => $this->inventaireRepository->count([
                'utilisateur' => $joueur,
            ]),
            'elo' => $joueur->getElo(),
            'equipe' => $this->equipeRepository->findOneBy([
                'utilisateur' => $joueur,
            ]),
            'statistiques' => $this->combatRepository
                ->calculerStatistiquesPourJoueur($joueur),
            'mouvementsPieces' => $this->mouvementPiecesRepository?->trouverPourJoueur(
                $joueur,
            ) ?? [],
            'recompenseQuotidienneDisponible' => $this->recompenseQuotidienneService?->estDisponible(
                $joueur,
            ) ?? true,
            'objectifs' => $this->objectifJoueurService?->construire($joueur) ?? [],
        ];
    }
}
