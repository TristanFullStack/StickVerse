<?php

namespace App\Service;

use App\Entity\Combat;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\ClockInterface;

final class ReglesMatchmakingService
{
    public const ECART_ELO_INITIAL = 100;
    public const ECART_ELO_PAR_PALIER = 50;
    public const ECART_ELO_MAXIMUM = 400;
    public const ECART_PUISSANCE_INITIAL_POURCENT = 10;
    public const ECART_PUISSANCE_PAR_PALIER_POURCENT = 5;
    public const ECART_PUISSANCE_MAXIMUM_POURCENT = 40;
    public const DUREE_PALIER_SECONDES = 30;

    public function __construct(
        private readonly ?ClockInterface $clock = null,
    ) {
    }

    /**
     * @return array{
     *     attenteSecondes: int,
     *     ecartEloMaximum: int,
     *     ecartPuissanceMaximumPourcent: int
     * }
     */
    public function criteresPour(Combat $combat): array
    {
        $attenteSecondes = max(
            0,
            ($this->clock ?? Clock::get())->now()->getTimestamp()
            - $combat->getDateCreation()->getTimestamp(),
        );
        $palier = intdiv(
            $attenteSecondes,
            self::DUREE_PALIER_SECONDES,
        );

        return [
            'attenteSecondes' => $attenteSecondes,
            'ecartEloMaximum' => min(
                self::ECART_ELO_MAXIMUM,
                self::ECART_ELO_INITIAL
                + ($palier * self::ECART_ELO_PAR_PALIER),
            ),
            'ecartPuissanceMaximumPourcent' => min(
                self::ECART_PUISSANCE_MAXIMUM_POURCENT,
                self::ECART_PUISSANCE_INITIAL_POURCENT
                + ($palier * self::ECART_PUISSANCE_PAR_PALIER_POURCENT),
            ),
        ];
    }

    public function estCompatible(
        Combat $combatEnAttente,
        int $eloCandidat,
        int $puissanceCandidat,
        int $puissanceEnAttente,
    ): bool {
        if ($puissanceCandidat <= 0 || $puissanceEnAttente <= 0) {
            return false;
        }

        $criteres = $this->criteresPour($combatEnAttente);
        $ecartElo = abs(
            $combatEnAttente->getJoueur1()->getElo() - $eloCandidat,
        );
        $ecartPuissancePourcent = $this->ecartPuissancePourcent(
            $puissanceCandidat,
            $puissanceEnAttente,
        );

        return $ecartElo <= $criteres['ecartEloMaximum']
            && $ecartPuissancePourcent
                <= $criteres['ecartPuissanceMaximumPourcent'];
    }

    public function ecartPuissancePourcent(
        int $premierePuissance,
        int $secondePuissance,
    ): float {
        $reference = max($premierePuissance, $secondePuissance);

        if ($reference <= 0) {
            return 100.0;
        }

        return (
            abs($premierePuissance - $secondePuissance)
            / $reference
        ) * 100;
    }
}
