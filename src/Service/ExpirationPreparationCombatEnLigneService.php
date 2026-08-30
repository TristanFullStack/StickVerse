<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\User;
use App\Repository\CombatRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\Clock\ClockInterface;

final class ExpirationPreparationCombatEnLigneService
{
    public const RESULTAT_ANNULE = 'annule';
    public const RESULTAT_FORFAIT = 'forfait';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatRepository $combatRepository,
        private readonly ClockInterface $clock,
    ) {
    }

    public function expirerSiNecessaire(int $combatId): ?string
    {
        return $this->entityManager->wrapInTransaction(
            function () use ($combatId): ?string {
                $combat = $this->combatRepository
                    ->trouverAvecVerrouEcriture($combatId);

                if (!$combat instanceof Combat) {
                    throw new LogicException(
                        'Le combat demandé est introuvable.'
                    );
                }

                if (!$combat->estPreparationExpiree($this->clock->now())) {
                    return null;
                }

                $joueur1 = $combat->getJoueur1();
                $joueur2 = $combat->getJoueur2();

                if (!$joueur2 instanceof User) {
                    throw new LogicException(
                        'La préparation nécessite deux joueurs.'
                    );
                }

                $joueur1Pret = $combat->estPret($joueur1);
                $joueur2Pret = $combat->estPret($joueur2);

                if ($joueur1Pret xor $joueur2Pret) {
                    $combat->setGagnant(
                        $joueur1Pret ? $joueur1 : $joueur2
                    );
                    $combat->setStatut(Combat::STATUT_FORFAIT);

                    return self::RESULTAT_FORFAIT;
                }

                $combat->setGagnant(null);
                $combat->setStatut(Combat::STATUT_ANNULE);

                return self::RESULTAT_ANNULE;
            }
        );
    }
}
