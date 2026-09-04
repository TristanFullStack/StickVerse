<?php

namespace App\Service;

use App\Entity\Stickman;

/**
 * Interprète les passifs de combat sans jamais exécuter de code fourni par
 * l'administration ou le navigateur.
 *
 * Les effets sont volontairement bornés à des règles déterministes et
 * déclaratives. De nouveaux types peuvent être ajoutés ici sans disperser
 * les règles dans le moteur de résolution.
 */
final class PassifCombatService
{
    public const TYPE_BONUS_ATTAQUE_POURCENTAGE = 'bonus_attaque_pct';
    public const TYPE_BONUS_DEFENSE_POURCENTAGE = 'bonus_defense_pct';
    public const BONUS_MAXIMUM = 50;
    public const PASSIFS_MAXIMUM_PAR_CARTE = 6;
    public const PUISSANCE_MAXIMUM = 500;

    /**
     * Identifiants des passifs contextuels disponibles dans l’administration.
     * Les règles de déclenchement restent centralisées dans ce service afin
     * d’éviter d’exécuter du code provenant du JSON administrable.
     */
    public const TYPES_CONTEXTUELS = [
        'rage', 'execution', 'precision', 'protecteur', 'rempart',
        'assaut_coordonne', 'tir_disperse', 'bouclier_arcanique', 'duelliste',
        'commandement', 'formation', 'opportuniste', 'protection', 'duel',
        'mobilite', 'rempart_leger', 'perforation_i', 'sang_froid', 'sentinelle',
        'furie', 'perforation_ii', 'barriere', 'charge_groupee', 'chasseur',
        'bastion', 'riposte', 'serment', 'combustion', 'predateur',
        'rune_defensive', 'combat_singulier', 'resilience', 'precision_spectrale',
        'forteresse', 'maitre_du_duel', 'tempete_divisee', 'egide',
        'autorite_imperiale', 'moisson', 'citadelle', 'percee_aube', 'mur_aube',
        'blitz', 'premier_sang', 'fenetre_tactique', 'verrouillage', 'surcharge',
        'fortification', 'crepuscule', 'mur_crepuscule', 'apocalypse',
        'derniere_citadelle', 'cri_meute', 'doctrine_defensive',
        'acceleration_temporelle', 'egide_croissante', 'lame_solaire',
        'finition', 'serment_initial', 'protection_juree', 'cadence_temporelle',
        'danse_divisee', 'aura_fortifiee', 'citadelle_double', 'ordre_charge',
        'heritage_general', 'fureur_crepusculaire', 'moisson_finale',
        'doctrine_focus', 'doctrine_split', 'duo_discipline', 'egide_aube',
        'egide_zenith', 'egide_crepuscule',
    ];

    /**
     * Libellés affichés dans l’outil d’administration et sur les cartes.
     * Une valeur personnalisée peut toujours être indiquée dans le JSON.
     *
     * @return array<string, string>
     */
    public function definitions(): array
    {
        $contextuels = [];
        foreach (self::TYPES_CONTEXTUELS as $type) {
            $contextuels[$type] = ucfirst(str_replace('_', ' ', $type));
        }

        return [
            self::TYPE_BONUS_ATTAQUE_POURCENTAGE => 'Bonus ATQ permanent',
            self::TYPE_BONUS_DEFENSE_POURCENTAGE => 'Bonus DEF permanent',
            ...$contextuels,
        ];
    }

    /** @return list<string> */
    public function typesDisponibles(): array
    {
        return array_keys($this->definitions());
    }

    /**
     * Convertit les bonus de combat en points de puissance de carte.
     *
     * La conversion reprend les coefficients officiels du score de puissance
     * (attaque = 2, défense = 1,5). Un passif différé est compté comme un
     * potentiel de la carte afin que sa valeur reste comparable dès la
     * sélection de l'équipe.
     */
    public function contributionPuissance(
        Stickman $stickman,
        float $coefficientAttaque,
        float $coefficientDefense,
    ): int {
        $attaque = max(0, $stickman->getAttaque() ?? 0);
        $defense = max(0, $stickman->getDefense() ?? 0);
        $bonusAttaque = 0;
        $bonusDefense = 0;
        $puissanceDirecte = 0;

        foreach ($this->passifsDe($stickman) as $passif) {
            if (array_key_exists('puissance', $passif)) {
                $puissanceDirecte += $passif['puissance'];
                continue;
            }
            if ($this->typeContribueAttaque($passif['type'])) {
                $bonusAttaque += $passif['valeur'];
            } elseif ($this->typeContribueDefense($passif['type'])) {
                $bonusDefense += $passif['valeur'];
            } elseif ($this->typeIgnoreDefense($passif['type'])) {
                // La pénétration augmente le potentiel offensif : elle est
                // donc valorisée avec le coefficient d’attaque.
                $bonusAttaque += $passif['valeur'];
            }
        }

        $bonusAttaque = min(self::BONUS_MAXIMUM, $bonusAttaque);
        $bonusDefense = min(self::BONUS_MAXIMUM, $bonusDefense);

        return max(0, $puissanceDirecte + (int) round(
            ($attaque * $coefficientAttaque * $bonusAttaque / 100)
            + ($defense * $coefficientDefense * $bonusDefense / 100),
        ));
    }

    /**
     * @param list<Stickman> $stickmen
     * @param array<string, mixed> $contexte
     */
    public function bonusAttaquePourcentage(
        array $stickmen,
        int $numeroRound,
        array $contexte = [],
    ): int {
        $total = $this->bonusPourcentage(
            $stickmen,
            self::TYPE_BONUS_ATTAQUE_POURCENTAGE,
            $numeroRound,
        );

        foreach ($this->acteursPour($stickmen, $contexte, 'attaquants') as $acteur) {
            foreach ($this->passifsDe($acteur['stickman']) as $passif) {
                if ($passif['type'] === self::TYPE_BONUS_ATTAQUE_POURCENTAGE) {
                    continue;
                }

                if ($this->passifAttaqueActif($passif, $acteur, $contexte, $numeroRound)) {
                    $total += $this->valeurActive($passif, $numeroRound);
                }
            }
        }

        return min(self::BONUS_MAXIMUM, max(0, $total));
    }

    /**
     * @param list<Stickman> $stickmen
     * @param array<string, mixed> $contexte
     */
    public function bonusDefensePourcentage(
        array $stickmen,
        int $numeroRound = 1,
        array $contexte = [],
    ): int {
        $total = $this->bonusPourcentage(
            $stickmen,
            self::TYPE_BONUS_DEFENSE_POURCENTAGE,
            $numeroRound,
        );

        foreach ($this->acteursPour($stickmen, $contexte, 'defenseurs') as $acteur) {
            foreach ($this->passifsDe($acteur['stickman']) as $passif) {
                if ($passif['type'] === self::TYPE_BONUS_DEFENSE_POURCENTAGE) {
                    continue;
                }

                if ($this->passifDefenseActif($passif, $acteur, $contexte, $numeroRound)) {
                    $total += $this->valeurActive($passif, $numeroRound);
                }
            }
        }

        return min(self::BONUS_MAXIMUM, max(0, $total));
    }

    /**
     * Retourne uniquement les passifs valides et actifs pour ce round.
     *
     * @param list<Stickman> $stickmen
     * @param array<string, mixed> $contexte
     * @return list<array{nom: string, description: string, type: string, valeur: int}>
     */
    public function passifsActifs(
        array $stickmen,
        int $numeroRound = 1,
        array $contexte = [],
    ): array
    {
        $actifs = [];

        $contexteCible = $contexte !== [] ? $contexte : [
            'attaquants' => $this->acteursPour($stickmen, [], 'attaquants'),
            'defenseurs' => $this->acteursPour($stickmen, [], 'defenseurs'),
        ];

        foreach (($contexteCible['attaquants'] ?? []) as $acteur) {
            if (!is_array($acteur) || !($acteur['stickman'] ?? null) instanceof Stickman) {
                continue;
            }
            $stickman = $acteur['stickman'];
            foreach ($this->passifsDe($stickman) as $passif) {
                if ($this->passifAttaqueActif($passif, $acteur, $contexte, $numeroRound)) {
                    $actifs[] = $this->normaliserPassifActif($passif);
                }
            }
        }

        foreach (($contexteCible['defenseurs'] ?? []) as $acteur) {
            if (!is_array($acteur) || !($acteur['stickman'] ?? null) instanceof Stickman) {
                continue;
            }
            foreach ($this->passifsDe($acteur['stickman']) as $passif) {
                if ($this->passifDefenseActif($passif, $acteur, $contexte, $numeroRound)) {
                    $actifs[] = $this->normaliserPassifActif($passif);
                }
            }
        }

        return $actifs;
    }

    /**
     * Pourcentage de défense ignoré par les passifs offensifs de l'impact.
     *
     * @param list<Stickman> $stickmen
     * @param array<string, mixed> $contexte
     */
    public function ignoreDefensePourcentage(
        array $stickmen,
        int $numeroRound = 1,
        array $contexte = [],
    ): int {
        $total = 0;

        foreach ($this->acteursPour($stickmen, $contexte, 'attaquants') as $acteur) {
            foreach ($this->passifsDe($acteur['stickman']) as $passif) {
                if (
                    in_array(
                        $passif['type'],
                        ['precision', 'perforation_i', 'perforation_ii', 'precision_spectrale'],
                        true,
                    )
                    && $this->passifDansFenetre($passif, $numeroRound)
                ) {
                    $total += $passif['valeur'];
                }
            }
        }

        return min(self::BONUS_MAXIMUM, max(0, $total));
    }

    /**
     * @param list<Stickman> $stickmen
     * @param array<string, mixed> $contexte
     * @return list<array{stickman: Stickman, pvActuels: int, pvMaximum: int, partenaireVivant: bool, protegeAllie: bool}>
     */
    private function acteursPour(array $stickmen, array $contexte, string $cle): array
    {
        $acteurs = $contexte[$cle] ?? null;

        if (!is_array($acteurs) || $acteurs === []) {
            return array_map(
                static fn (Stickman $stickman): array => [
                    'stickman' => $stickman,
                    'pvActuels' => max(0, $stickman->getPv() ?? 0),
                    'pvMaximum' => max(0, $stickman->getPv() ?? 0),
                    'partenaireVivant' => true,
                    'protegeAllie' => false,
                ],
                $stickmen,
            );
        }

        return array_values(array_filter(
            $acteurs,
            static fn (mixed $acteur): bool => is_array($acteur)
                && ($acteur['stickman'] ?? null) instanceof Stickman,
        ));
    }

    /** @param array<string, mixed> $passif */
    private function passifAttaqueActif(
        array $passif,
        array $acteur,
        array $contexte,
        int $numeroRound,
    ): bool {
        $type = $passif['type'];

        if ($type === self::TYPE_BONUS_ATTAQUE_POURCENTAGE) {
            return $this->passifDansFenetre($passif, $numeroRound);
        }

        if (!$this->passifDansFenetre($passif, $numeroRound)) {
            return false;
        }

        $ratioPv = $this->ratioPv($acteur);
        $ratioCible = $this->ratioPvCible($contexte);

        return match ($type) {
            'rage', 'furie', 'combustion' => $ratioPv < .4,
            'precision', 'perforation_i', 'perforation_ii', 'precision_spectrale' => true,
            'execution', 'chasseur', 'predateur', 'finition' => $ratioCible < .3,
            'opportuniste', 'moisson', 'moisson_finale' => $ratioCible < .25,
            'assaut_coordonne', 'charge_groupee', 'doctrine_focus' => ($contexte['modeAttaque'] ?? null) === 'focus',
            'tir_disperse', 'mobilite', 'tempete_divisee', 'danse_divisee', 'doctrine_split' => ($contexte['modeAttaque'] ?? null) === 'split',
            'formation' => (bool) ($acteur['partenaireVivant'] ?? false) && $numeroRound <= 2,
            'duo_discipline' => (bool) ($acteur['partenaireVivant'] ?? false),
            'commandement', 'autorite_imperiale', 'heritage_general' => !($acteur['partenaireVivant'] ?? true),
            'percee_aube', 'premier_sang', 'lame_solaire', 'ordre_charge' => $numeroRound <= 3,
            'fenetre_tactique' => $numeroRound >= 4 && $numeroRound <= 8,
            'surcharge' => $numeroRound >= 4 && $numeroRound <= 8,
            'crepuscule', 'apocalypse', 'fureur_crepusculaire' => $numeroRound >= 10,
            'cri_meute' => true,
            'acceleration_temporelle' => min(32, $numeroRound * $passif['valeur']) > 0,
            default => false,
        };
    }

    /** @param array<string, mixed> $passif */
    private function passifDefenseActif(
        array $passif,
        array $acteur,
        array $contexte,
        int $numeroRound,
    ): bool {
        $type = $passif['type'];

        if ($type === self::TYPE_BONUS_DEFENSE_POURCENTAGE) {
            return $this->passifDansFenetre($passif, $numeroRound);
        }

        if (!$this->passifDansFenetre($passif, $numeroRound)) {
            return false;
        }

        $ratioPv = $this->ratioPv($acteur);
        $equipesAttaquantes = max(0, (int) ($contexte['equipesAttaquantSurCible'] ?? 1));

        return match ($type) {
            'protecteur', 'protection', 'sentinelle', 'serment', 'egide' => (bool) ($acteur['protegeAllie'] ?? false),
            'protection_juree' => (bool) ($acteur['protegeAllie'] ?? false) && (bool) ($contexte['doubleDefense'] ?? false),
            'rempart', 'rempart_leger', 'bastion', 'forteresse', 'citadelle', 'citadelle_double' => (bool) ($contexte['doubleDefense'] ?? false),
            'duelliste', 'duel', 'riposte', 'combat_singulier', 'maitre_du_duel' => $equipesAttaquantes === 1,
            'bouclier_arcanique', 'barriere', 'rune_defensive', 'serment_initial' => (bool) ($contexte['premiereDefense'] ?? false),
            'sang_froid' => $ratioPv < .4,
            'resilience' => $ratioPv < .35,
            'mur_aube', 'egide_aube' => $numeroRound <= 3,
            'verrouillage', 'fortification', 'egide_zenith' => $numeroRound >= 4 && $numeroRound <= 8,
            'mur_crepuscule', 'derniere_citadelle', 'egide_crepuscule' => $numeroRound >= 10,
            'doctrine_defensive', 'aura_fortifiee' => true,
            'egide_croissante' => min(32, $numeroRound * $passif['valeur']) > 0,
            default => false,
        };
    }

    /** @param array<string, mixed> $acteur */
    private function ratioPv(array $acteur): float
    {
        $maximum = max(1, (int) ($acteur['pvMaximum'] ?? 0));

        return max(0, min(1, (int) ($acteur['pvActuels'] ?? $maximum) / $maximum));
    }

    /** @param array<string, mixed> $contexte */
    private function ratioPvCible(array $contexte): float
    {
        $maximum = max(1, (int) ($contexte['pvMaximumCible'] ?? $contexte['pvActuels'] ?? 0));

        return max(0, min(1, (int) ($contexte['pvActuels'] ?? $maximum) / $maximum));
    }

    /** @param array<string, mixed> $passif */
    private function passifDansFenetre(array $passif, int $numeroRound): bool
    {
        return max(1, (int) ($passif['a_partir_round'] ?? 1)) <= $numeroRound;
    }

    /** @param array<string, mixed> $passif */
    private function valeurActive(array $passif, int $numeroRound): int
    {
        $valeur = (int) $passif['valeur'];

        if (in_array($passif['type'], ['acceleration_temporelle', 'egide_croissante'], true)) {
            return min(32, max(0, $valeur * $numeroRound));
        }

        if (in_array($passif['type'], ['cadence_temporelle'], true)) {
            return min(24, max(0, $valeur * $numeroRound));
        }

        return $valeur;
    }

    /** @param array<string, mixed> $passif */
    private function normaliserPassifActif(array $passif): array
    {
        $normalise = [
            'nom' => $passif['nom'],
            'description' => $passif['description'],
            'type' => $passif['type'],
            'valeur' => $passif['valeur'],
        ];
        if (array_key_exists('puissance', $passif)) {
            $normalise['puissance'] = $passif['puissance'];
        }
        if (array_key_exists('actif', $passif)) {
            $normalise['actif'] = $passif['actif'];
        }

        return $normalise;
    }

    private function typeContribueAttaque(string $type): bool
    {
        return in_array(
            $type,
            [
                self::TYPE_BONUS_ATTAQUE_POURCENTAGE,
                'rage', 'execution', 'assaut_coordonne', 'tir_disperse', 'formation',
                'commandement', 'opportuniste', 'mobilite', 'furie', 'charge_groupee',
                'chasseur', 'combustion', 'predateur', 'tempete_divisee', 'autorite_imperiale',
                'moisson', 'percee_aube', 'blitz', 'premier_sang', 'fenetre_tactique',
                'surcharge', 'crepuscule', 'apocalypse', 'cri_meute',
                'acceleration_temporelle', 'lame_solaire', 'finition', 'cadence_temporelle',
                'danse_divisee', 'ordre_charge', 'heritage_general', 'fureur_crepusculaire',
                'moisson_finale', 'doctrine_focus', 'doctrine_split', 'duo_discipline',
            ],
            true,
        );
    }

    private function typeContribueDefense(string $type): bool
    {
        return in_array(
            $type,
            [
                self::TYPE_BONUS_DEFENSE_POURCENTAGE,
                'protecteur', 'rempart', 'bouclier_arcanique', 'duelliste', 'protection',
                'duel', 'rempart_leger', 'sang_froid', 'sentinelle', 'barriere', 'bastion',
                'riposte', 'serment', 'rune_defensive', 'combat_singulier', 'resilience',
                'forteresse', 'maitre_du_duel', 'egide', 'citadelle', 'mur_aube',
                'verrouillage', 'fortification', 'mur_crepuscule', 'derniere_citadelle',
                'doctrine_defensive', 'egide_croissante', 'aura_fortifiee', 'serment_initial',
                'protection_juree', 'citadelle_double', 'egide_aube', 'egide_zenith',
                'egide_crepuscule',
            ],
            true,
        );
    }

    private function typeIgnoreDefense(string $type): bool
    {
        return in_array($type, ['precision', 'perforation_i', 'perforation_ii', 'precision_spectrale'], true);
    }

    /**
     * @param list<Stickman> $stickmen
     */
    private function bonusPourcentage(
        array $stickmen,
        string $typeRecherche,
        int $numeroRound,
    ): int {
        $total = 0;

        foreach ($this->passifsActifs($stickmen, $numeroRound) as $passif) {
            if ($passif['type'] === $typeRecherche) {
                $total += $passif['valeur'];
            }
        }

        return min(self::BONUS_MAXIMUM, max(0, $total));
    }

    /**
     * @return list<array{nom: string, description: string, type: string, valeur: int, puissance?: int, a_partir_round?: int}>
     */
    private function passifsDe(Stickman $stickman): array
    {
        $passifs = [];

        foreach (array_slice($stickman->getPassifs(), 0, self::PASSIFS_MAXIMUM_PAR_CARTE) as $passif) {
            if (!is_array($passif)) {
                continue;
            }
            if (array_key_exists('actif', $passif) && $passif['actif'] === false) {
                continue;
            }

            $type = $passif['type'] ?? null;
            $valeur = $passif['valeur'] ?? null;

            if (
                !is_string($type)
                || !is_numeric($valeur)
                || !in_array(
                    $type,
                    [
                        self::TYPE_BONUS_ATTAQUE_POURCENTAGE,
                        self::TYPE_BONUS_DEFENSE_POURCENTAGE,
                        ...self::TYPES_CONTEXTUELS,
                    ],
                    true,
                )
            ) {
                continue;
            }

            $valeur = (int) round((float) $valeur);

            if ($valeur < 0 || $valeur > self::BONUS_MAXIMUM) {
                continue;
            }

            $nom = $passif['nom'] ?? 'Passif';
            $description = $passif['description'] ?? '';
            $minimumRound = $passif['a_partir_round'] ?? 1;
            $puissance = $passif['puissance'] ?? null;

            if (!is_string($nom) || !is_string($description) || !is_numeric($minimumRound)) {
                continue;
            }
            if ($puissance !== null && (!is_numeric($puissance) || (float) $puissance < 0 || (float) $puissance > self::PUISSANCE_MAXIMUM)) {
                continue;
            }

            $entree = [
                'nom' => substr(trim($nom), 0, 80),
                'description' => substr(trim($description), 0, 255),
                'type' => $type,
                'valeur' => $valeur,
            ];
            if ($puissance !== null) {
                $entree['puissance'] = (int) round((float) $puissance);
            }
            $minimumRound = max(1, (int) $minimumRound);

            if ($minimumRound > 1) {
                $entree['a_partir_round'] = $minimumRound;
            }

            $passifs[] = $entree;
        }

        return $passifs;
    }
}
