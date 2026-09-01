<?php

namespace App\Service;

use App\Entity\MouvementPieces;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Repository\MouvementPiecesRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;

final class MissionsJoueurService
{
    private const MISSIONS = [
        'quotidiennes' => [
            'combat' => [
                'libelle' => 'Un combat aujourd’hui',
                'description' => 'Terminer ou abandonner un combat.',
                'cible' => 1,
                'recompense' => 50,
                'metrique' => 'combats',
            ],
            'victoire' => [
                'libelle' => 'Victoire du jour',
                'description' => 'Remporter un combat.',
                'cible' => 1,
                'recompense' => 75,
                'metrique' => 'victoires',
            ],
        ],
        'hebdomadaires' => [
            'combats' => [
                'libelle' => 'Régulier de la semaine',
                'description' => 'Participer à cinq combats.',
                'cible' => 5,
                'recompense' => 150,
                'metrique' => 'combats',
            ],
            'victoires' => [
                'libelle' => 'Série de victoires',
                'description' => 'Remporter trois combats.',
                'cible' => 3,
                'recompense' => 200,
                'metrique' => 'victoires',
            ],
            'caisses' => [
                'libelle' => 'Ouvertures de la semaine',
                'description' => 'Ouvrir trois caisses payantes.',
                'cible' => 3,
                'recompense' => 100,
                'metrique' => 'caisses',
            ],
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
     * @return array{quotidiennes: list<array<string, mixed>>, hebdomadaires: list<array<string, mixed>>}
     */
    public function construire(
        User $joueur,
        ?DateTimeImmutable $maintenant = null,
    ): array {
        $maintenant ??= new DateTimeImmutable();

        return [
            'quotidiennes' => $this->construirePeriode(
                $joueur,
                'quotidiennes',
                $maintenant->setTime(0, 0),
                $maintenant->format('Y-m-d'),
            ),
            'hebdomadaires' => $this->construirePeriode(
                $joueur,
                'hebdomadaires',
                $maintenant->modify('monday this week')->setTime(0, 0),
                $maintenant->format('o-\\WW'),
            ),
        ];
    }

    public function reclamer(
        User $joueur,
        string $periode,
        string $mission,
        ?DateTimeImmutable $maintenant = null,
    ): int {
        if (!isset(self::MISSIONS[$periode][$mission])) {
            throw new InvalidArgumentException('Cette mission est inconnue.');
        }
        if ($joueur->getId() === null) {
            throw new LogicException('Le joueur doit être enregistré avant de réclamer une mission.');
        }

        $maintenant ??= new DateTimeImmutable();
        $debut = $periode === 'quotidiennes'
            ? $maintenant->setTime(0, 0)
            : $maintenant->modify('monday this week')->setTime(0, 0);
        $clePeriode = $periode === 'quotidiennes'
            ? $maintenant->format('Y-m-d')
            : $maintenant->format('o-\\WW');
        $cle = sprintf('mission:%s:%s:%s', $periode, $clePeriode, $mission);

        return $this->entityManager->wrapInTransaction(
            function () use ($joueur, $periode, $mission, $debut, $cle): int {
                $verrouille = $this->userRepository
                    ->trouverAvecVerrouEcriture((int) $joueur->getId());
                if (!$verrouille instanceof User) {
                    throw new LogicException('Le joueur demandé est introuvable.');
                }
                if ($verrouille->aReclameObjectif($cle)) {
                    return 0;
                }

                $element = $this->construireUneMission(
                    $verrouille,
                    $periode,
                    $mission,
                    $debut,
                    $cle,
                );
                if (!$element['disponible']) {
                    return 0;
                }

                $montant = $element['recompense'];
                $verrouille->crediterPieces($montant)->marquerObjectifReclame($cle);
                $this->mouvementPiecesService?->enregistrer(
                    $verrouille,
                    $montant,
                    MouvementPieces::TYPE_RECOMPENSE_OBJECTIF,
                    'Mission '.$element['libelle'],
                );

                return $montant;
            },
        );
    }

    /** @return list<array<string, mixed>> */
    private function construirePeriode(
        User $joueur,
        string $periode,
        DateTimeImmutable $debut,
        string $clePeriode,
    ): array {
        $resultat = [];
        foreach (self::MISSIONS[$periode] as $id => $definition) {
            $cle = sprintf('mission:%s:%s:%s', $periode, $clePeriode, $id);
            $resultat[] = $this->construireUneMission(
                $joueur,
                $periode,
                $id,
                $debut,
                $cle,
            );
        }

        return $resultat;
    }

    /** @return array<string, mixed> */
    private function construireUneMission(
        User $joueur,
        string $periode,
        string $id,
        DateTimeImmutable $debut,
        string $cle,
    ): array {
        $definition = self::MISSIONS[$periode][$id];
        $progression = match ($definition['metrique']) {
            'combats' => $this->combatRepository->compterDepuisPourJoueur($joueur, $debut),
            'victoires' => $this->combatRepository->compterVictoiresDepuisPourJoueur($joueur, $debut),
            'caisses' => $this->mouvementPiecesRepository->compterDepuisPourJoueurEtType(
                $joueur,
                MouvementPieces::TYPE_ACHAT_CAISSE,
                $debut,
            ),
            default => 0,
        };

        return [
            'id' => $id,
            'periode' => $periode,
            'libelle' => $definition['libelle'],
            'description' => $definition['description'],
            'progression' => min($definition['cible'], $progression),
            'cible' => $definition['cible'],
            'recompense' => $definition['recompense'],
            'reclame' => $joueur->aReclameObjectif($cle),
            'disponible' => !$joueur->aReclameObjectif($cle)
                && $progression >= $definition['cible'],
        ];
    }
}
