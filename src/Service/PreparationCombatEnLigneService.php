<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\User;
use App\Repository\CombatRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use LogicException;

final class PreparationCombatEnLigneService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatRepository $combatRepository,
    ) {
    }

    public function confirmer(
        int $combatId,
        User $joueur,
    ): Combat {
        return $this->entityManager->wrapInTransaction(
            function () use ($combatId, $joueur): Combat {
                $combat = $this->combatRepository
                    ->trouverAvecVerrouEcriture($combatId);

                if (!$combat instanceof Combat) {
                    throw new LogicException(
                        'Le combat demandé est introuvable.'
                    );
                }

                try {
                    $combat->confirmerPret($joueur);
                } catch (InvalidArgumentException $exception) {
                    throw new LogicException(
                        $exception->getMessage(),
                        previous: $exception,
                    );
                }

                return $combat;
            }
        );
    }
}
