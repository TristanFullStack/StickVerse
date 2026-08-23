<?php

namespace App\Service;

use App\Entity\Combat;
use App\Repository\CombatRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\Clock\ClockInterface;

final class ExpirationCombatEnAttenteService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatRepository $combatRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function expirerSiNecessaire(int $combatId): bool
    {
        return $this->entityManager->wrapInTransaction(
            function () use ($combatId): bool {
                $combat = $this->combatRepository
                    ->trouverAvecVerrouEcriture($combatId);

                if (!$combat instanceof Combat) {
                    throw new LogicException(
                        'Le combat demandé est introuvable.'
                    );
                }

                if (!$combat->estAttenteExpiree($this->clock->now())) {
                    return false;
                }

                $combat->setGagnant(null);
                $combat->setStatut(Combat::STATUT_ANNULE);

                return true;
            }
        );
    }
}
