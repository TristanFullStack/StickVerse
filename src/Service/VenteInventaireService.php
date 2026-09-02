<?php

namespace App\Service;

use App\Entity\Inventaire;
use App\Entity\User;
use App\Repository\InventaireRepository;
use App\Repository\EquipeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

final class VenteInventaireService
{
    public function __construct(
        private readonly InventaireRepository $inventaireRepository,
        private readonly EquipeRepository $equipeRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MouvementPiecesService $mouvementPiecesService,
        private readonly ScorePuissanceService $scorePuissanceService,
    ) {
    }

    /**
     * @param array<int|string, int|string> $quantites Par identifiant d’inventaire.
     */
    public function vendre(User $joueur, array $quantites): int
    {
        $joueurId = $joueur->getId();
        if ($joueurId === null) {
            throw new InvalidArgumentException('Le joueur doit être enregistré.');
        }

        return $this->entityManager->wrapInTransaction(function () use ($joueurId, $quantites): int {
            $verrouille = $this->userRepository->trouverAvecVerrouEcriture($joueurId);
            if (!$verrouille instanceof User) {
                throw new InvalidArgumentException('Le joueur est introuvable.');
            }

            $total = 0;
            $stickmanIdsEquipes = array_fill_keys(
                $this->equipeRepository->stickmanIdsUtilisesPourUtilisateur($verrouille),
                true,
            );
            foreach ($quantites as $identifiant => $quantiteBrute) {
                $id = filter_var($identifiant, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $quantite = filter_var($quantiteBrute, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($id === false || $quantite === false) {
                    throw new InvalidArgumentException('La sélection de vente est invalide.');
                }

                $inventaire = $this->inventaireRepository->findOneBy([
                    'id' => $id,
                    'utilisateur' => $verrouille,
                ]);
                if (!$inventaire instanceof Inventaire || ($inventaire->getQuantite() ?? 0) < $quantite) {
                    throw new InvalidArgumentException('Une carte sélectionnée n’est plus disponible.');
                }

                $stickman = $inventaire->getStickman();
                if ($stickman === null) {
                    throw new InvalidArgumentException('Une carte sélectionnée est invalide.');
                }

                $reste = ($inventaire->getQuantite() ?? 0) - $quantite;
                if ($reste < 1 && $stickman->getId() !== null && isset($stickmanIdsEquipes[$stickman->getId()])) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Impossible de vendre la dernière carte « %s » : elle est utilisée dans une équipe. Retire-la d’abord de toutes tes équipes.',
                            $stickman->getNom() ?? 'sans nom',
                        ),
                    );
                }

                $prixUnitaire = $this->scorePuissanceService->calculerStickman($stickman)
                    * max(1, (int) $stickman->getRarete());
                $total += $prixUnitaire * $quantite;
                if ($reste === 0) {
                    $this->entityManager->remove($inventaire);
                } else {
                    $inventaire->setQuantite($reste);
                }
            }

            if ($total <= 0) {
                throw new InvalidArgumentException('Aucune carte à vendre.');
            }

            $verrouille->crediterPieces($total);
            $this->mouvementPiecesService->enregistrer(
                $verrouille,
                $total,
                \App\Entity\MouvementPieces::TYPE_VENTE_STICKMAN,
                sprintf('%d carte(s) vendue(s)', array_sum(array_map('intval', $quantites))),
            );
            $this->entityManager->flush();

            return $total;
        });
    }
}
