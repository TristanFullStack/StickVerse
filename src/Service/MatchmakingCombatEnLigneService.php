<?php

namespace App\Service;

use App\Entity\Combat;
use App\Entity\Equipe;
use App\Entity\User;
use App\Repository\CombatRepository;
use LogicException;

final class MatchmakingCombatEnLigneService
{
    public function __construct(
        private readonly CombatRepository $combatRepository,
        private readonly CreationCombatEnLigneService $creationService,
        private readonly RejoindreCombatEnLigneService $rejoindreService,
        private readonly ScorePuissanceService $scorePuissanceService,
        private readonly ReglesMatchmakingService $reglesService,
    ) {
    }

    public function rechercher(User $joueur, Equipe $equipe): Combat
    {
        $combatActif = $this->combatRepository
            ->trouverActifPourJoueur($joueur);

        if ($combatActif instanceof Combat) {
            // Une relance peut arriver juste après que l’adversaire a rejoint
            // le combat. Retourner ce combat rend la recherche idempotente et
            // permet au navigateur de récupérer immédiatement le bon état.
            if (!$combatActif->estPrive()) {
                return $combatActif;
            }

            throw new LogicException(
                'Le joueur participe déjà à un combat actif.'
            );
        }

        $puissanceEquipe = $this->scorePuissanceService
            ->calculerEquipe($equipe);
        $candidats = [];

        foreach (
            $this->combatRepository->trouverDisponiblesPour($joueur)
            as $combat
        ) {
            $puissanceAdverse = $this->scorePuissanceService
                ->calculerCombatPourJoueur(
                    $combat,
                    $combat->getJoueur1(),
                );

            if (
                !$this->reglesService->estCompatible(
                    $combat,
                    $joueur->getElo(),
                    $puissanceEquipe,
                    $puissanceAdverse,
                )
            ) {
                continue;
            }

            $candidats[] = [
                'combat' => $combat,
                'ecartElo' => abs(
                    $combat->getJoueur1()->getElo() - $joueur->getElo(),
                ),
                'ecartPuissance' => $this->reglesService
                    ->ecartPuissancePourcent(
                        $puissanceEquipe,
                        $puissanceAdverse,
                    ),
            ];
        }

        usort(
            $candidats,
            static fn (array $premier, array $second): int =>
                [$premier['ecartPuissance'], $premier['ecartElo']]
                <=> [$second['ecartPuissance'], $second['ecartElo']],
        );

        foreach ($candidats as $candidat) {
            $combat = $candidat['combat'];
            $combatId = $combat->getId();

            if ($combatId === null) {
                continue;
            }

            try {
                return $this->rejoindreService->rejoindre(
                    $combatId,
                    $joueur,
                    $equipe,
                );
            } catch (LogicException $exception) {
                if (
                    $exception->getMessage()
                    !== 'Ce combat n’est plus disponible.'
                    && !str_contains(
                        $exception->getMessage(),
                        'a expiré',
                    )
                ) {
                    throw $exception;
                }
            }
        }

        return $this->creationService->creer(
            $joueur,
            $equipe,
            false,
        );
    }
}
