<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\Equipe;
use App\Entity\User;
use App\Repository\CombatRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final class CreationCombatEnLigneService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatRepository $combatRepository,
        private readonly CreationCombattantsCombatService $creationCombattantsService,
    ) {
    }

    public function creer(
        User $joueur,
        Equipe $equipe,
    ): Combat {
        return $this->entityManager->wrapInTransaction(
            function () use (
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

                $combatActif = $this->combatRepository
                    ->trouverActifPourJoueur($joueur);

                if ($combatActif instanceof Combat) {
                    throw new LogicException(
                        'Le joueur participe déjà à un combat actif.'
                    );
                }

                $combat = new Combat($joueur);

                $this->creationCombattantsService
                    ->creerPourJoueur(
                        $combat,
                        $joueur,
                        $equipe,
                    );

                $this->entityManager->persist($combat);

                return $combat;
            }
        );
    }
}