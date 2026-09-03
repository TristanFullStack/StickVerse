<?php

namespace App\Service;

use App\Entity\Stickman;

/**
 * Interprète les passifs de combat sans jamais exécuter de code fourni par
 * l'administration ou le navigateur.
 *
 * Les effets pris en charge dans cette première version sont volontairement
 * limités à des bonus de statistiques bornés. De nouveaux types peuvent être
 * ajoutés ici sans disperser les règles dans le moteur de résolution.
 */
final class PassifCombatService
{
    public const TYPE_BONUS_ATTAQUE_POURCENTAGE = 'bonus_attaque_pct';
    public const TYPE_BONUS_DEFENSE_POURCENTAGE = 'bonus_defense_pct';
    public const BONUS_MAXIMUM = 50;
    public const PASSIFS_MAXIMUM_PAR_CARTE = 6;

    /**
     * @param list<Stickman> $stickmen
     */
    public function bonusAttaquePourcentage(
        array $stickmen,
        int $numeroRound,
    ): int {
        return $this->bonusPourcentage(
            $stickmen,
            self::TYPE_BONUS_ATTAQUE_POURCENTAGE,
            $numeroRound,
        );
    }

    /**
     * @param list<Stickman> $stickmen
     */
    public function bonusDefensePourcentage(
        array $stickmen,
        int $numeroRound = 1,
    ): int {
        return $this->bonusPourcentage(
            $stickmen,
            self::TYPE_BONUS_DEFENSE_POURCENTAGE,
            $numeroRound,
        );
    }

    /**
     * Retourne uniquement les passifs valides et actifs pour ce round.
     *
     * @param list<Stickman> $stickmen
     * @return list<array{nom: string, description: string, type: string, valeur: int}>
     */
    public function passifsActifs(array $stickmen, int $numeroRound = 1): array
    {
        $actifs = [];

        foreach ($stickmen as $stickman) {
            foreach ($this->passifsDe($stickman) as $passif) {
                $type = $passif['type'];
                $minimumRound = $passif['a_partir_round'] ?? 1;

                if ($minimumRound > $numeroRound) {
                    continue;
                }

                if (
                    !in_array(
                        $type,
                        [
                            self::TYPE_BONUS_ATTAQUE_POURCENTAGE,
                            self::TYPE_BONUS_DEFENSE_POURCENTAGE,
                        ],
                        true,
                    )
                ) {
                    continue;
                }

                $actifs[] = [
                    'nom' => $passif['nom'],
                    'description' => $passif['description'],
                    'type' => $type,
                    'valeur' => $passif['valeur'],
                ];
            }
        }

        return $actifs;
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
     * @return list<array{nom: string, description: string, type: string, valeur: int, a_partir_round?: int}>
     */
    private function passifsDe(Stickman $stickman): array
    {
        $passifs = [];

        foreach (array_slice($stickman->getPassifs(), 0, self::PASSIFS_MAXIMUM_PAR_CARTE) as $passif) {
            if (!is_array($passif)) {
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

            if (!is_string($nom) || !is_string($description) || !is_numeric($minimumRound)) {
                continue;
            }

            $entree = [
                'nom' => substr(trim($nom), 0, 80),
                'description' => substr(trim($description), 0, 255),
                'type' => $type,
                'valeur' => $valeur,
            ];
            $minimumRound = max(1, (int) $minimumRound);

            if ($minimumRound > 1) {
                $entree['a_partir_round'] = $minimumRound;
            }

            $passifs[] = $entree;
        }

        return $passifs;
    }
}
