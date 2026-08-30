<?php

namespace App\Service;

use App\Entity\User;
use DateTimeImmutable;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;

final class LimitationTentativesInvitationCombatService
{
    public function __construct(
        private readonly RateLimiterFactoryInterface $combatInvitationLimiter,
    ) {
    }

    public function consommer(
        User $joueur,
        ?string $adresseIp,
    ): ?DateTimeImmutable {
        $cle = hash(
            'sha256',
            $joueur->getUserIdentifier()
            .'|'
            .($adresseIp ?? 'adresse-inconnue'),
        );

        $limite = $this->combatInvitationLimiter
            ->create($cle)
            ->consume();

        return $limite->isAccepted()
            ? null
            : $limite->getRetryAfter();
    }
}
