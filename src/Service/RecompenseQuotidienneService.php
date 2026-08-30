<?php

namespace App\Service;

use App\Entity\MouvementPieces;
use App\Entity\User;
use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final class RecompenseQuotidienneService
{
    public const MONTANT = 25;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?MouvementPiecesService $mouvementPiecesService = null,
    ) {
    }

    public function estDisponible(
        User $joueur,
        ?DateTimeImmutable $maintenant = null,
    ): bool {
        $derniere = $joueur->getDateDerniereRecompenseQuotidienne();

        return $derniere === null
            || $derniere->format('Y-m-d') !== ($maintenant ?? new DateTimeImmutable())->format('Y-m-d');
    }

    public function reclamer(
        User $joueur,
        ?DateTimeImmutable $maintenant = null,
    ): int {
        $joueurId = $joueur->getId();

        if ($joueurId === null) {
            throw new LogicException(
                'Le joueur doit être enregistré avant de réclamer une récompense.'
            );
        }

        $maintenant ??= new DateTimeImmutable();

        return $this->entityManager->wrapInTransaction(
            function () use ($joueurId, $maintenant): int {
                $joueurVerrouille = $this->userRepository
                    ->trouverAvecVerrouEcriture($joueurId);

                if (!$joueurVerrouille instanceof User) {
                    throw new LogicException('Le joueur demandé est introuvable.');
                }

                if (!$this->estDisponible($joueurVerrouille, $maintenant)) {
                    return 0;
                }

                $joueurVerrouille
                    ->crediterPieces(self::MONTANT)
                    ->setDateDerniereRecompenseQuotidienne($maintenant);

                $this->mouvementPiecesService?->enregistrer(
                    $joueurVerrouille,
                    self::MONTANT,
                    MouvementPieces::TYPE_RECOMPENSE_QUOTIDIENNE,
                    'Récompense quotidienne',
                );

                return self::MONTANT;
            },
        );
    }
}
