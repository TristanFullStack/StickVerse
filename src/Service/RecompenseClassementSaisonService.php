<?php

namespace App\Service;

use App\Entity\ClassementSaisonJoueur;
use App\Entity\CollectionJeu;
use App\Entity\MouvementPieces;
use App\Entity\User;
use App\Repository\ClassementSaisonJoueurRepository;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final class RecompenseClassementSaisonService
{
    public function __construct(
        private readonly ClassementSaisonJoueurRepository $classementRepository,
        private readonly UserRepository $userRepository,
        private readonly DivisionClassementService $divisionService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?MouvementPiecesService $mouvementPiecesService = null,
    ) {
    }

    public function estDisponible(
        ClassementSaisonJoueur $classement,
        ?DateTimeImmutable $maintenant = null,
    ): bool {
        return !$classement->estRecompenseReclamee()
            && $classement->getSaison()->estTermineeA(
                $maintenant ?? new DateTimeImmutable(),
            );
    }

    public function reclamer(
        User $joueur,
        CollectionJeu $saison,
        ?DateTimeImmutable $maintenant = null,
    ): int {
        if ($joueur->getId() === null || $saison->getId() === null) {
            throw new LogicException(
                'Le joueur et la saison doivent être enregistrés.',
            );
        }

        $maintenant ??= new DateTimeImmutable();

        return $this->entityManager->wrapInTransaction(
            function () use ($joueur, $saison, $maintenant): int {
                $joueurVerrouille = $this->userRepository
                    ->trouverAvecVerrouEcriture((int) $joueur->getId());

                if (!$joueurVerrouille instanceof User) {
                    throw new LogicException('Le joueur demandé est introuvable.');
                }

                $classement = $this->classementRepository
                    ->trouverAvecVerrouEcriture(
                        $joueurVerrouille,
                        $saison,
                    );

                if (
                    !$classement instanceof ClassementSaisonJoueur
                    || !$this->estDisponible($classement, $maintenant)
                ) {
                    return 0;
                }

                $division = $this->divisionService
                    ->informationsPour($classement->getElo());
                $montant = $division['recompense'];

                $joueurVerrouille->crediterPieces($montant);
                $classement->marquerRecompenseReclamee($maintenant);
                $this->mouvementPiecesService?->enregistrer(
                    $joueurVerrouille,
                    $montant,
                    MouvementPieces::TYPE_RECOMPENSE_SAISON,
                    sprintf(
                        'Récompense Saison %d — division %s',
                        $saison->getSaison(),
                        $division['nom'],
                    ),
                );

                return $montant;
            },
        );
    }
}
