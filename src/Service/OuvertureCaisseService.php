<?php

namespace App\Service;

use App\Dto\ResultatOuvertureCaisse;
use App\Entity\Caisse;
use App\Entity\CaisseStickman;
use App\Entity\Inventaire;
use App\Entity\MouvementPieces;
use App\Entity\OuvertureCaisse;
use App\Entity\Stickman;
use App\Entity\User;
use App\Exception\OuvertureCaisseImpossibleException;
use App\Exception\SoldePiecesInsuffisantException;
use App\Repository\InventaireRepository;
use App\Repository\OuvertureCaisseRepository;
use App\Repository\StickmanRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final class OuvertureCaisseService
{
    public function __construct(
        private readonly InventaireRepository $inventaireRepository,
        private readonly UserRepository $userRepository,
        private readonly OuvertureCaisseRepository $ouvertureRepository,
        private readonly StickmanRepository $stickmanRepository,
        private readonly TirageCaisseService $tirageCaisseService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ?MouvementPiecesService $mouvementPiecesService = null,
        private readonly ?InventaireCaisseService $inventaireCaisseService = null,
    ) {
    }

    /**
     * Compatibilité avec les appels historiques du service.
     * Les nouvelles ouvertures HTTP utilisent toujours ouvrirAvecJeton().
     */
    public function ouvrir(Caisse $caisse, User $utilisateur): ?Stickman
    {
        try {
            return $this->ouvrirAvecJeton(
                $caisse,
                $utilisateur,
                bin2hex(random_bytes(32)),
            )->stickman;
        } catch (OuvertureCaisseImpossibleException) {
            return null;
        }
    }

    public function ouvrirAvecJeton(
        Caisse $caisse,
        User $utilisateur,
        string $jeton,
    ): ResultatOuvertureCaisse {
        if (preg_match('/^[a-f0-9]{64}$/', $jeton) !== 1) {
            throw new OuvertureCaisseImpossibleException(
                'La demande d’ouverture est invalide. Recharge la page puis réessaie.'
            );
        }

        $utilisateurId = $utilisateur->getId();

        if ($utilisateurId === null || $caisse->getId() === null) {
            throw new LogicException(
                'Le joueur et la caisse doivent être enregistrés avant une ouverture.'
            );
        }

        return $this->entityManager->wrapInTransaction(
            function () use ($caisse, $utilisateurId, $jeton): ResultatOuvertureCaisse {
                $joueur = $this->userRepository
                    ->trouverAvecVerrouEcriture($utilisateurId);

                if (!$joueur instanceof User) {
                    throw new LogicException('Le joueur demandé est introuvable.');
                }

                $ouvertureExistante = $this->ouvertureRepository->findOneBy([
                    'jeton' => $jeton,
                ]);

                if ($ouvertureExistante instanceof OuvertureCaisse) {
                    return $this->rejouerOuverture(
                        $ouvertureExistante,
                        $caisse,
                        $joueur,
                    );
                }

                $contenus = $this->tirageCaisseService
                    ->contenusEligibles($caisse);
                $stickman = $this->tirageCaisseService
                    ->tirer($caisse, $contenus);

                $prix = max(0, (int) $caisse->getPrix());
                $caissePossedee = $this->inventaireCaisseService?->consommer(
                    $joueur,
                    $caisse,
                ) ?? false;
                $caisseOfferte = $caissePossedee || (
                    $caisse->getSlug() === Caisse::SLUG_PREMIERS_RENFORTS
                    && $joueur->consommerCaissePremiersRenforts()
                );

                if (!$caisseOfferte && !$joueur->debiterPieces($prix)) {
                    throw new SoldePiecesInsuffisantException(
                        'Tu ne possèdes pas assez de pièces pour cette caisse.'
                    );
                }

                [$inventaire, $nouveau] = $this->ajouterDansInventaire(
                    $joueur,
                    $stickman,
                );

                if (!$caisseOfferte && $prix > 0) {
                    $this->mouvementPiecesService?->enregistrer(
                        $joueur,
                        -$prix,
                        MouvementPieces::TYPE_ACHAT_CAISSE,
                        'Ouverture de la caisse '.($caisse->getNom() ?? ''),
                    );
                }

                // Rend l’inventaire visible aux requêtes de progression tout en
                // restant dans la même transaction atomique.
                $this->entityManager->flush();

                [$collectionPossedes, $collectionTotal] = $this
                    ->calculerProgressionCollection($joueur, $caisse, $contenus);
                $quantiteApres = max(1, $inventaire->getQuantite() ?? 1);
                $ouverture = new OuvertureCaisse(
                    $jeton,
                    $joueur,
                    $caisse,
                    $stickman,
                    $quantiteApres,
                    $nouveau,
                    $collectionPossedes,
                    $collectionTotal,
                );

                $this->entityManager->persist($ouverture);
                // L'identifiant du reçu fait partie de la réponse. Ce flush reste
                // inclus dans la transaction et ne peut donc pas créer un demi-achat.
                $this->entityManager->flush();

                $ouvertureId = $ouverture->getId();
                if ($ouvertureId === null) {
                    throw new LogicException('Le reçu d’ouverture n’a pas été enregistré.');
                }

                return $this->construireResultat(
                    $ouvertureId,
                    $caisse,
                    $stickman,
                    $contenus,
                    $quantiteApres,
                    $nouveau,
                    $collectionPossedes,
                    $collectionTotal,
                    $joueur,
                );
            }
        );
    }

    private function rejouerOuverture(
        OuvertureCaisse $ouverture,
        Caisse $caisseDemandee,
        User $joueur,
    ): ResultatOuvertureCaisse {
        if (
            $ouverture->getUtilisateur()->getId() !== $joueur->getId()
            || $ouverture->getCaisse()?->getId() !== $caisseDemandee->getId()
        ) {
            throw new OuvertureCaisseImpossibleException(
                'Cet identifiant d’ouverture a déjà été utilisé.'
            );
        }

        $stickman = $ouverture->getStickman();
        $ouvertureId = $ouverture->getId();

        if (!$stickman instanceof Stickman || $ouvertureId === null) {
            throw new OuvertureCaisseImpossibleException(
                'Le résultat de cette ancienne ouverture n’est plus disponible.'
            );
        }

        $contenus = $this->tirageCaisseService
            ->contenusEligibles($caisseDemandee);

        return $this->construireResultat(
            $ouvertureId,
            $caisseDemandee,
            $stickman,
            $contenus,
            $ouverture->getQuantiteApres(),
            $ouverture->isNouveau(),
            $ouverture->getCollectionPossedes(),
            $ouverture->getCollectionTotal(),
            $joueur,
            true,
        );
    }

    /** @return array{Inventaire, bool} */
    private function ajouterDansInventaire(
        User $utilisateur,
        Stickman $stickman,
    ): array {
        $inventaire = $this->inventaireRepository->findOneBy([
            'utilisateur' => $utilisateur,
            'stickman' => $stickman,
        ]);
        $nouveau = !$inventaire instanceof Inventaire;

        if ($nouveau) {
            $inventaire = (new Inventaire())->setStickman($stickman);
            $utilisateur->addInventaire($inventaire);
            $this->entityManager->persist($inventaire);
        } else {
            $inventaire->setQuantite(($inventaire->getQuantite() ?? 0) + 1);
        }

        return [$inventaire, $nouveau];
    }

    /**
     * @param list<CaisseStickman> $contenus
     * @return array{int, int}
     */
    private function calculerProgressionCollection(
        User $joueur,
        Caisse $caisse,
        array $contenus,
    ): array {
        $collection = $caisse->getCollectionJeu();
        if ($collection !== null) {
            return [
                $this->inventaireRepository
                    ->compterStickmenDistinctsPourCollection($joueur, $collection),
                $this->stickmanRepository
                    ->compterActifsPourCollection($collection),
            ];
        }

        $stickmenCibles = array_values(array_filter(array_map(
                static fn (CaisseStickman $contenu): ?Stickman => $contenu->getStickman(),
                $contenus,
            )));

        $idsCibles = [];
        foreach ($stickmenCibles as $stickman) {
            if ($stickman->getId() !== null) {
                $idsCibles[$stickman->getId()] = true;
            }
        }

        $idsPossedes = [];
        foreach ($joueur->getInventaires() as $inventaire) {
            $stickmanId = $inventaire->getStickman()?->getId();
            if (
                $stickmanId !== null
                && isset($idsCibles[$stickmanId])
                && ($inventaire->getQuantite() ?? 0) > 0
            ) {
                $idsPossedes[$stickmanId] = true;
            }
        }

        return [count($idsPossedes), count($idsCibles)];
    }

    /** @param list<CaisseStickman> $contenus */
    private function construireResultat(
        int $ouvertureId,
        Caisse $caisse,
        Stickman $stickman,
        array $contenus,
        int $quantiteApres,
        bool $nouveau,
        int $collectionPossedes,
        int $collectionTotal,
        User $joueur,
        bool $rejouee = false,
    ): ResultatOuvertureCaisse {
        $stickmenDisponibles = [];
        foreach ($contenus as $contenu) {
            $contenuStickman = $contenu->getStickman();
            if ($contenuStickman instanceof Stickman) {
                $stickmenDisponibles[] = $contenuStickman;
            }
        }

        $possessions = $this->inventaireCaisseService?->compterPourCaisse($joueur, $caisse) ?? 0;
        $caissesRestantes = $possessions + (
            $caisse->getSlug() === Caisse::SLUG_PREMIERS_RENFORTS
                ? $joueur->getCaissesPremiersRenforts()
                : 0
        );
        $peutOuvrirEncore = $stickmenDisponibles !== [] && (
            $possessions > 0
            || $caisse->getSlug() === Caisse::SLUG_PREMIERS_RENFORTS
                && $joueur->getCaissesPremiersRenforts() > 0
            || $joueur->getPieces() >= max(0, (int) $caisse->getPrix())
        );

        return new ResultatOuvertureCaisse(
            $ouvertureId,
            $caisse,
            $stickman,
            $stickmenDisponibles,
            $quantiteApres,
            $nouveau,
            $collectionPossedes,
            $collectionTotal,
            $joueur->getPieces(),
            $joueur->getCaissesPremiersRenforts(),
            $peutOuvrirEncore,
            $rejouee,
            $caissesRestantes,
        );
    }
}
