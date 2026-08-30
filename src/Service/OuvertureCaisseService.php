<?php

namespace App\Service;

use App\Entity\Caisse;
use App\Entity\Inventaire;
use App\Entity\Stickman;
use App\Entity\User;
use App\Exception\SoldePiecesInsuffisantException;
use App\Repository\InventaireRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final class OuvertureCaisseService
{
    public function __construct(
        private readonly InventaireRepository $inventaireRepository,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function ouvrir(Caisse $caisse, User $utilisateur): ?Stickman
    {
        $contenus = $caisse->getContenus();

        if ($contenus->isEmpty()) {
            return null;
        }

        $poidsTotal = $caisse->getPoidsTotal();

        if ($poidsTotal <= 0) {
            return null;
        }

        $numeroTire = random_int(1, $poidsTotal);

        $poidsCumule = 0;

        foreach ($contenus as $contenu) {
            $poidsCumule += $contenu->getPoids() ?? 0;

            if ($numeroTire <= $poidsCumule) {
                $stickman = $contenu->getStickman();

                if ($stickman === null) {
                    return null;
                }

                return $this->payerEtAjouter(
                    $caisse,
                    $utilisateur,
                    $stickman,
                );
            }
        }

        return null;
    }

    private function payerEtAjouter(
        Caisse $caisse,
        User $utilisateur,
        Stickman $stickman,
    ): Stickman {
        $utilisateurId = $utilisateur->getId();

        if ($utilisateurId === null) {
            throw new LogicException(
                'Le joueur doit être enregistré avant une ouverture.'
            );
        }

        return $this->entityManager->wrapInTransaction(
            function () use (
                $caisse,
                $utilisateurId,
                $stickman,
            ): Stickman {
                $joueurVerrouille = $this->userRepository
                    ->trouverAvecVerrouEcriture($utilisateurId);

                if (!$joueurVerrouille instanceof User) {
                    throw new LogicException(
                        'Le joueur demandé est introuvable.'
                    );
                }

                $prix = max(0, (int) $caisse->getPrix());

                if (!$joueurVerrouille->debiterPieces($prix)) {
                    throw new SoldePiecesInsuffisantException(
                        'Tu ne possèdes pas assez de pièces pour cette caisse.'
                    );
                }

                $this->ajouterDansInventaire(
                    $joueurVerrouille,
                    $stickman,
                );

                return $stickman;
            }
        );
    }

    private function ajouterDansInventaire(
        User $utilisateur,
        Stickman $stickman,
    ): void
    {
        $inventaire = $this->inventaireRepository->findOneBy([
            'utilisateur' => $utilisateur,
            'stickman' => $stickman,
        ]);

        if ($inventaire === null) {
            $inventaire = new Inventaire();
            $inventaire->setUtilisateur($utilisateur);
            $inventaire->setStickman($stickman);

            $this->entityManager->persist($inventaire);
        } else {
            $inventaire->setQuantite(
                ($inventaire->getQuantite() ?? 0) + 1
            );
        }
    }
}
