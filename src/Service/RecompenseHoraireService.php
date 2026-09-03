<?php

namespace App\Service;

use App\Entity\MouvementPieces;
use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final class RecompenseHoraireService
{
    public const MONTANT_PAR_HEURE = 100;
    public const MONTANT_MAXIMUM = 500;
    public const HEURES_MAXIMUM = 5;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?MouvementPiecesService $mouvementPiecesService = null,
    ) {
    }

    public function montantDisponible(
        User $joueur,
        ?DateTimeImmutable $maintenant = null,
    ): int {
        $date = $joueur->getDateDerniereRecompenseHoraire();
        if ($date === null) {
            return 0;
        }

        $secondes = max(0, ($maintenant ?? new DateTimeImmutable())->getTimestamp() - $date->getTimestamp());
        $heures = min(self::HEURES_MAXIMUM, intdiv($secondes, 3600));

        return $heures * self::MONTANT_PAR_HEURE;
    }

    public function reclamer(
        User $joueur,
        ?DateTimeImmutable $maintenant = null,
    ): int {
        $joueurId = $joueur->getId();
        if ($joueurId === null) {
            throw new LogicException('Le joueur doit être enregistré avant de réclamer une récompense.');
        }

        $maintenant ??= new DateTimeImmutable();

        return $this->entityManager->wrapInTransaction(
            function () use ($joueurId, $maintenant): int {
                $joueurVerrouille = $this->userRepository->trouverAvecVerrouEcriture($joueurId);
                if (!$joueurVerrouille instanceof User) {
                    throw new LogicException('Le joueur demandé est introuvable.');
                }

                $montant = $this->montantDisponible($joueurVerrouille, $maintenant);
                if ($montant <= 0) {
                    return 0;
                }

                $date = $joueurVerrouille->getDateDerniereRecompenseHoraire();
                if ($date === null) {
                    return 0;
                }

                $secondes = max(0, $maintenant->getTimestamp() - $date->getTimestamp());
                if ($secondes >= self::HEURES_MAXIMUM * 3600) {
                    $nouvelleDate = $maintenant;
                } else {
                    $heures = intdiv($secondes, 3600);
                    $nouvelleDate = $date->modify(sprintf('+%d hours', $heures));
                }

                $joueurVerrouille
                    ->crediterPieces($montant)
                    ->setDateDerniereRecompenseHoraire($nouvelleDate);
                $this->mouvementPiecesService?->enregistrer(
                    $joueurVerrouille,
                    $montant,
                    MouvementPieces::TYPE_RECOMPENSE_HORAIRE,
                    'Récompense horaire',
                );

                return $montant;
            },
        );
    }
}
