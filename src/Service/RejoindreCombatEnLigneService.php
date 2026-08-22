<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\Equipe;
use App\Entity\User;
use App\Repository\CombatRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final class RejoindreCombatEnLigneService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatRepository $combatRepository,
        private readonly CreationCombattantsCombatService $creationCombattantsService,
    ) {
    }

    public function rejoindre(
        int $combatId,
        User $joueur,
        Equipe $equipe,
    ): Combat {
        return $this->entityManager->wrapInTransaction(
            function () use (
                $combatId,
                $joueur,
                $equipe,
            ): Combat {
                if ($joueur->getId() === null) {
                    throw new LogicException(
                        'Le joueur doit être enregistré en base de données.'
                    );
                }

                if ($equipe->getId() === null) {
                    throw new LogicException(
                        'L’équipe doit être enregistrée en base de données.'
                    );
                }

                $combat = $this->combatRepository
                    ->trouverAvecVerrouEcriture($combatId);

                if (!$combat instanceof Combat) {
                    throw new LogicException(
                        'Le combat demandé est introuvable.'
                    );
                }

                if (
                    !$combat->estEnAttente()
                    || $combat->getJoueur2() !== null
                ) {
                    throw new LogicException(
                        'Ce combat n’est plus disponible.'
                    );
                }

                if (
                    $this->utilisateursIdentiques(
                        $joueur,
                        $combat->getJoueur1(),
                    )
                ) {
                    throw new LogicException(
                        'Un joueur ne peut pas rejoindre son propre combat.'
                    );
                }

                $combatActif = $this->combatRepository
                    ->trouverActifPourJoueur($joueur);

                if ($combatActif instanceof Combat) {
                    throw new LogicException(
                        'Le joueur participe déjà à un combat actif.'
                    );
                }

                $combat->setJoueur2($joueur);

                $this->creationCombattantsService
                    ->creerPourJoueur(
                        $combat,
                        $joueur,
                        $equipe,
                    );

                $combat->setStatut(
                    Combat::STATUT_EN_COURS
                );

                return $combat;
            }
        );
    }

    private function utilisateursIdentiques(
        User $premier,
        User $second,
    ): bool {
        if ($premier === $second) {
            return true;
        }

        return $premier->getId() !== null
            && $premier->getId() === $second->getId();
    }
}