<?php

namespace App\Service;

use App\Entity\MouvementPieces;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ConsoleAdministrationService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly MouvementPiecesService $mouvementPiecesService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{statut: string, message: string}
     */
    public function executer(string $commande): array
    {
        $commande = trim($commande);

        if ($commande === '') {
            return $this->erreur('Écris une commande à exécuter.');
        }

        $arguments = preg_split('/\s+/', $commande) ?: [];
        $nomCommande = strtolower((string) array_shift($arguments));

        return match ($nomCommande) {
            'aide', 'help' => $this->aide(),
            'give', 'crediter' => $this->crediter($arguments),
            'recherche', 'find' => $this->rechercher($arguments),
            default => $this->erreur(
                sprintf(
                    'Commande inconnue : %s. Utilise « help » pour voir les commandes.',
                    $nomCommande,
                ),
            ),
        };
    }

    /**
     * @return array{statut: string, message: string}
     */
    private function aide(): array
    {
        return [
            'statut' => 'aide',
            'message' => implode("\n", [
                'Commandes disponibles :',
                'help                         Afficher cette aide.',
                'give <pseudo> <montant>      Ajouter des pièces à un joueur.',
                'recherche <pseudo>           Afficher le solde d’un joueur.',
            ]),
        ];
    }

    /**
     * @param list<string> $arguments
     * @return array{statut: string, message: string}
     */
    private function crediter(array $arguments): array
    {
        if (count($arguments) !== 2) {
            return $this->erreur('Syntaxe : give <pseudo> <montant>.');
        }

        [$pseudo, $montantTexte] = $arguments;

        if (!preg_match('/^[\p{L}\p{N}_-]+$/u', $pseudo)) {
            return $this->erreur('Le pseudo contient des caractères invalides.');
        }

        if (!ctype_digit($montantTexte) || (int) $montantTexte <= 0) {
            return $this->erreur('Le montant doit être un nombre entier positif.');
        }

        $montant = (int) $montantTexte;
        $joueur = $this->userRepository->findOneBy(['pseudo' => $pseudo]);

        if (!$joueur instanceof User) {
            return $this->erreur(sprintf('Aucun joueur ne possède le pseudo « %s ».', $pseudo));
        }

        $this->entityManager->wrapInTransaction(function () use ($joueur, $montant): void {
            $joueur->crediterPieces($montant);
            $this->mouvementPiecesService->enregistrer(
                $joueur,
                $montant,
                MouvementPieces::TYPE_ADMIN_CREDIT,
                'Crédit administrateur',
            );
        });

        return [
            'statut' => 'succes',
            'message' => sprintf(
                '%d pièces ajoutées à %s. Nouveau solde : %d pièces.',
                $montant,
                $joueur->getPseudo(),
                $joueur->getPieces(),
            ),
        ];
    }

    /**
     * @param list<string> $arguments
     * @return array{statut: string, message: string}
     */
    private function rechercher(array $arguments): array
    {
        if (count($arguments) !== 1) {
            return $this->erreur('Syntaxe : recherche <pseudo>.');
        }

        $pseudo = $arguments[0];
        $joueur = $this->userRepository->findOneBy(['pseudo' => $pseudo]);

        if (!$joueur instanceof User) {
            return $this->erreur(sprintf('Aucun joueur ne possède le pseudo « %s ».', $pseudo));
        }

        return [
            'statut' => 'succes',
            'message' => sprintf(
                '%s — %d pièces.',
                $joueur->getPseudo(),
                $joueur->getPieces(),
            ),
        ];
    }

    /**
     * @return array{statut: string, message: string}
     */
    private function erreur(string $message): array
    {
        return [
            'statut' => 'erreur',
            'message' => $message,
        ];
    }
}
