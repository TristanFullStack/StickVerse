<?php

namespace App\Service;

use App\Entity\Caisse;
use App\Entity\MouvementPieces;
use App\Entity\User;
use App\Exception\SoldePiecesInsuffisantException;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class BoutiqueService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly InventaireCaisseService $inventaireCaisseService,
        private readonly MouvementPiecesService $mouvementPiecesService,
    ) {
    }

    public function acheter(User $joueur, Caisse $caisse, int $quantite = 1): void
    {
        if ($quantite < 1 || $quantite > 100) {
            throw new \InvalidArgumentException('La quantité doit être comprise entre 1 et 100 caisses.');
        }
        $id = $joueur->getId();
        if ($id === null) {
            throw new \LogicException('Le joueur doit être enregistré.');
        }

        $this->entityManager->wrapInTransaction(function () use ($id, $caisse, $quantite): void {
            $verrouille = $this->userRepository->trouverAvecVerrouEcriture($id);
            if (!$verrouille instanceof User) {
                throw new \LogicException('Le joueur est introuvable.');
            }

            $prixUnitaire = max(0, (int) $caisse->getPrix());
            $prix = $prixUnitaire * $quantite;
            if (!$verrouille->debiterPieces($prix)) {
                throw new SoldePiecesInsuffisantException('Tu ne possèdes pas assez de pièces pour acheter cette caisse.');
            }

            $this->inventaireCaisseService->ajouter($verrouille, $caisse, $quantite);
            if ($prix > 0) {
                $this->mouvementPiecesService->enregistrer(
                    $verrouille,
                    -$prix,
                    MouvementPieces::TYPE_ACHAT_CAISSE,
                    sprintf('Achat de %d caisse(s) %s', $quantite, $caisse->getNom() ?? ''),
                );
            }
            $this->entityManager->flush();
        });
    }
}
