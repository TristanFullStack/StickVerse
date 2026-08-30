<?php

namespace App\Service;

use App\Entity\MouvementPieces;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Repository\MouvementPiecesRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;

final class ObjectifJoueurService
{
    public const OBJECTIF_PREMIER_COMBAT = 'premier_combat';
    public const OBJECTIF_CINQ_COMBATS = 'cinq_combats';
    public const OBJECTIF_PREMIERE_CAISSE = 'premiere_caisse';

    private const OBJECTIFS = [
        self::OBJECTIF_PREMIER_COMBAT => [
            'libelle' => 'Premier combat',
            'description' => 'Terminer ou abandonner un premier combat.',
            'cible' => 1,
            'recompense' => 50,
        ],
        self::OBJECTIF_CINQ_COMBATS => [
            'libelle' => 'Habitué des combats',
            'description' => 'Participer à cinq combats.',
            'cible' => 5,
            'recompense' => 100,
        ],
        self::OBJECTIF_PREMIERE_CAISSE => [
            'libelle' => 'Première ouverture',
            'description' => 'Ouvrir une première caisse payante.',
            'cible' => 1,
            'recompense' => 50,
        ],
    ];

    public function __construct(
        private readonly CombatRepository $combatRepository,
        private readonly MouvementPiecesRepository $mouvementPiecesRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?MouvementPiecesService $mouvementPiecesService = null,
    ) {
    }

    /**
     * @return list<array{id: string, libelle: string, description: string, progression: int, cible: int, recompense: int, reclame: bool, disponible: bool}>
     */
    public function construire(User $joueur): array
    {
        $statistiques = $this->combatRepository
            ->calculerStatistiquesPourJoueur($joueur);
        $ouvertures = $this->mouvementPiecesRepository
            ->compterPourJoueurEtType(
                $joueur,
                MouvementPieces::TYPE_ACHAT_CAISSE,
            );

        $progressions = [
            self::OBJECTIF_PREMIER_COMBAT => $statistiques['total'],
            self::OBJECTIF_CINQ_COMBATS => $statistiques['total'],
            self::OBJECTIF_PREMIERE_CAISSE => $ouvertures,
        ];

        $objectifs = [];

        foreach (self::OBJECTIFS as $id => $definition) {
            $progression = min(
                $definition['cible'],
                $progressions[$id],
            );
            $reclame = $joueur->aReclameObjectif($id);

            $objectifs[] = [
                'id' => $id,
                'libelle' => $definition['libelle'],
                'description' => $definition['description'],
                'progression' => $progression,
                'cible' => $definition['cible'],
                'recompense' => $definition['recompense'],
                'reclame' => $reclame,
                'disponible' => !$reclame
                    && $progression >= $definition['cible'],
            ];
        }

        return $objectifs;
    }

    public function reclamer(User $joueur, string $objectif): int
    {
        if (!isset(self::OBJECTIFS[$objectif])) {
            throw new InvalidArgumentException('Cet objectif est inconnu.');
        }

        $joueurId = $joueur->getId();

        if ($joueurId === null) {
            throw new LogicException(
                'Le joueur doit être enregistré avant de réclamer un objectif.'
            );
        }

        return $this->entityManager->wrapInTransaction(
            function () use ($joueurId, $objectif): int {
                $joueurVerrouille = $this->userRepository
                    ->trouverAvecVerrouEcriture($joueurId);

                if (!$joueurVerrouille instanceof User) {
                    throw new LogicException('Le joueur demandé est introuvable.');
                }

                if ($joueurVerrouille->aReclameObjectif($objectif)) {
                    return 0;
                }

                $objectifConstruit = $this->construire($joueurVerrouille);
                $objectifDisponible = null;

                foreach ($objectifConstruit as $element) {
                    if ($element['id'] === $objectif) {
                        $objectifDisponible = $element;
                        break;
                    }
                }

                if ($objectifDisponible === null || !$objectifDisponible['disponible']) {
                    return 0;
                }

                $montant = $objectifDisponible['recompense'];
                $joueurVerrouille
                    ->crediterPieces($montant)
                    ->marquerObjectifReclame($objectif);

                $this->mouvementPiecesService?->enregistrer(
                    $joueurVerrouille,
                    $montant,
                    MouvementPieces::TYPE_RECOMPENSE_OBJECTIF,
                    'Récompense : '.$objectifDisponible['libelle'],
                );

                return $montant;
            },
        );
    }
}
