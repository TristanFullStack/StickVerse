<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\Equipe;
use App\Entity\User;
use App\Repository\CombatRepository;
use App\Repository\CollectionJeuRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final class CreationCombatEnLigneService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CombatRepository $combatRepository,
        private readonly UserRepository $userRepository,
        private readonly CreationCombattantsCombatService $creationCombattantsService,
        private readonly ?CollectionJeuRepository $collectionJeuRepository = null,
    ) {
    }

    public function creer(
        User $joueur,
        Equipe $equipe,
        bool $prive = false,
    ): Combat {
        return $this->entityManager->wrapInTransaction(
            function () use (
                $joueur,
                $equipe,
                $prive,
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

                $joueurVerrouille = $this->userRepository
                    ->trouverAvecVerrouEcriture($joueur->getId());

                if (!$joueurVerrouille instanceof User) {
                    throw new LogicException(
                        'Le joueur demandé est introuvable.'
                    );
                }

                $combatActif = $this->combatRepository
                    ->trouverActifPourJoueur($joueurVerrouille);

                if ($combatActif instanceof Combat) {
                    throw new LogicException(
                        'Le joueur participe déjà à un combat actif.'
                    );
                }

                $combat = (new Combat($joueurVerrouille))
                    ->setPrive($prive)
                    ->setCodeInvitation(
                        $this->genererCodeInvitation()
                    );

                if (!$prive) {
                    $combat->setSaisonClassement(
                        $this->collectionJeuRepository
                            ?->trouverSaisonActive(),
                    );
                }

                $this->creationCombattantsService
                    ->creerPourJoueur(
                        $combat,
                        $joueurVerrouille,
                        $equipe,
                    );

                $this->entityManager->persist($combat);

                return $combat;
            }
        );
    }

    private function genererCodeInvitation(): string
    {
        for ($tentative = 0; $tentative < 10; $tentative++) {
            $code = 'SV-'.strtoupper(bin2hex(random_bytes(3)));

            if (!$this->combatRepository->codeInvitationExiste($code)) {
                return $code;
            }
        }

        throw new LogicException(
            'Impossible de générer un code d’invitation unique.'
        );
    }
}
