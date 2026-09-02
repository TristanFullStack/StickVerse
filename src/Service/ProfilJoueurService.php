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
        private ?RecompenseHoraireService $recompenseHoraireService = null,
        private ?MissionsJoueurService $missionsJoueurService = null,
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
     *     recompenseHoraireDisponible: int
     *     objectifs: list<array{id: string, libelle: string, description: string, progression: int, cible: int, recompense: int, reclame: bool, disponible: bool}>
     *     missions: array{quotidiennes: list<array<string, mixed>>, hebdomadaires: list<array<string, mixed>>}
     * }
     */
    public function construire(User $joueur, ?int $saison = null): array
    {
        return [
            'nombreStickmen' => $this->inventaireRepository->count([
                'utilisateur' => $joueur,
            ]),
            'elo' => $joueur->getElo(),
            'equipe' => $this->equipeRepository->findOneBy([
                'utilisateur' => $joueur,
            ]),
            'statistiques' => $saison === null
                ? $this->combatRepository->calculerStatistiquesPourJoueur($joueur)
                : $this->combatRepository->calculerStatistiquesPourJoueurEtSaison($joueur, $saison),
            'mouvementsPieces' => $this->mouvementPiecesRepository?->trouverPourJoueur(
                $joueur,
            ) ?? [],
            'recompenseQuotidienneDisponible' => $this->recompenseQuotidienneService?->estDisponible(
                $joueur,
            ) ?? true,
            'recompenseHoraireDisponible' => $this->recompenseHoraireService?->montantDisponible(
                $joueur,
            ) ?? 0,
            'objectifs' => $this->objectifJoueurService?->construire($joueur) ?? [],
            'missions' => $this->missionsJoueurService?->construire($joueur) ?? [
                'quotidiennes' => [],
                'hebdomadaires' => [],
            ],
        ];
    }
}
