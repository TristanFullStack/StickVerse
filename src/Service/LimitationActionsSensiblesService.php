<?php

namespace App\Service;

use App\Entity\User;
use DateTimeImmutable;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

/**
 * Centralise les limites anti-abus des actions qui modifient le compte.
 *
 * La clé combine le compte et l'adresse IP afin d'éviter qu'un même compte
 * puisse contourner la limite en changeant de navigateur ou de connexion.
 */
final readonly class LimitationActionsSensiblesService
{
    public function __construct(
        private RateLimiterFactoryInterface $caisseOuvertureLimiter,
        private RateLimiterFactoryInterface $caisseAchatLimiter,
        private RateLimiterFactoryInterface $inventaireVenteLimiter,
        private RateLimiterFactoryInterface $recompenseLimiter,
        private RateLimiterFactoryInterface $matchmakingLimiter,
        private RateLimiterFactoryInterface $combatActionLimiter,
        private RateLimiterFactoryInterface $demandeReinitialisationLimiter,
    ) {
    }

    public function consommer(
        User $joueur,
        string $action,
        ?string $adresseIp,
    ): ?DateTimeImmutable {
        return $this->consommerPourCle(
            $action,
            $joueur->getUserIdentifier(),
            $adresseIp,
        );
    }

    public function consommerPourIdentifiant(
        string $identifiant,
        string $action,
        ?string $adresseIp,
    ): ?DateTimeImmutable {
        return $this->consommerPourCle($action, $identifiant, $adresseIp);
    }

    public function secondesAvant(?DateTimeImmutable $date): int
    {
        if ($date === null) {
            return 0;
        }

        return max(1, $date->getTimestamp() - time());
    }

    private function consommerPourCle(
        string $action,
        string $identifiant,
        ?string $adresseIp,
    ): ?DateTimeImmutable {
        $limite = $this->limiteurPour($action)
            ->create(hash('sha256', strtolower(trim($identifiant)).'|'.($adresseIp ?? 'adresse-inconnue')))
            ->consume();

        return $limite->isAccepted()
            ? null
            : $limite->getRetryAfter();
    }

    private function limiteurPour(string $action): RateLimiterFactoryInterface
    {
        return match ($action) {
            'caisse_ouverture' => $this->caisseOuvertureLimiter,
            'caisse_achat' => $this->caisseAchatLimiter,
            'inventaire_vente' => $this->inventaireVenteLimiter,
            'recompense' => $this->recompenseLimiter,
            'matchmaking' => $this->matchmakingLimiter,
            'combat_action' => $this->combatActionLimiter,
            'demande_reinitialisation' => $this->demandeReinitialisationLimiter,
            default => throw new \InvalidArgumentException('Limiteur inconnu : '.$action),
        };
    }
}
