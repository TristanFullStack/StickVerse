<?php

namespace App\Service;

use App\Entity\Passif;
use App\Entity\Stickman;

/**
 * Maintient les snapshots JSON des cartes à partir du catalogue central des
 * passifs. Les combats restent découplés de Doctrine et consomment toujours
 * un snapshot immuable.
 */
final class PassifAffectationService
{
    /** @param list<Passif> $passifs */
    public function snapshotsDepuis(array $passifs): array
    {
        $snapshots = [];
        foreach ($passifs as $passif) {
            if (!$passif instanceof Passif || count($snapshots) >= PassifCombatService::PASSIFS_MAXIMUM_PAR_CARTE) {
                break;
            }
            if (in_array($passif->getId(), array_column($snapshots, 'id'), true)) {
                continue;
            }
            $snapshots[] = $passif->versTableau();
        }

        return $snapshots;
    }

    /**
     * @param iterable<Stickman> $stickmen
     */
    public function synchroniser(
        Passif $passif,
        iterable $stickmen,
        ?string $ancienType = null,
        ?string $ancienNom = null,
    ): int {
        $modifies = 0;
        foreach ($stickmen as $stickman) {
            $snapshots = $stickman->getPassifs();
            $nouveaux = [];
            $aChange = false;
            foreach ($snapshots as $snapshot) {
                if (!is_array($snapshot)) {
                    continue;
                }
                $correspond = ($passif->getId() !== null && (int) ($snapshot['id'] ?? 0) === $passif->getId())
                    || ($ancienType !== null && $ancienNom !== null
                        && ($snapshot['type'] ?? null) === $ancienType
                        && ($snapshot['nom'] ?? null) === $ancienNom)
                    || (($snapshot['type'] ?? null) === $passif->getType() && ($snapshot['nom'] ?? null) === $passif->getNom());
                if ($correspond) {
                    $nouveaux[] = $passif->versTableau();
                    $aChange = true;
                } else {
                    $nouveaux[] = $snapshot;
                }
            }
            if ($aChange) {
                $stickman->setPassifs($nouveaux);
                ++$modifies;
            }
        }

        return $modifies;
    }

    /** @param iterable<Stickman> $stickmen */
    public function retirer(Passif $passif, iterable $stickmen): int
    {
        $modifies = 0;
        foreach ($stickmen as $stickman) {
            $restants = array_values(array_filter(
                $stickman->getPassifs(),
                static fn (mixed $snapshot): bool => !is_array($snapshot)
                    || (($snapshot['id'] ?? null) !== $passif->getId()
                        && (($snapshot['type'] ?? null) !== $passif->getType()
                            || ($snapshot['nom'] ?? null) !== $passif->getNom())),
            ));
            if (count($restants) !== count($stickman->getPassifs())) {
                $stickman->setPassifs($restants);
                ++$modifies;
            }
        }

        return $modifies;
    }
}
