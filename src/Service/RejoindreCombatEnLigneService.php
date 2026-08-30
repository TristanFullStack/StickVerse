<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\Equipe;
use App\Entity\User;
use App\Repository\CombatRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use Symfony\Component\Clock\Clock;
use Symfony\Component\Clock\ClockInterface;

final class RejoindreCombatEnLigneService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatRepository $combatRepository,
        private readonly CreationCombattantsCombatService $creationCombattantsService,
        private readonly ?ClockInterface $clock = null,
    ) {
    }

    public function rejoindre(
        int $combatId,
        User $joueur,
        Equipe $equipe,
        bool $avecCodeInvitation = false,
    ): Combat {
        $combatExpire = false;

        $combat = $this->entityManager->wrapInTransaction(
            function () use (
                $combatId,
                $joueur,
                $equipe,
                $avecCodeInvitation,
                &$combatExpire,
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
                    $combat->estAttenteExpiree(
                        ($this->clock ?? Clock::get())->now()
                    )
                ) {
                    $combat->setGagnant(null);
                    $combat->setStatut(Combat::STATUT_ANNULE);
                    $combatExpire = true;

                    return $combat;
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
                    $combat->estPrive()
                    && !$avecCodeInvitation
                ) {
                    throw new LogicException(
                        'Ce combat privé doit être rejoint avec son code d’invitation.'
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

        if ($combatExpire) {
            throw new LogicException(
                'Ce combat a expiré après 5 minutes sans adversaire.'
            );
        }

        return $combat;
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
