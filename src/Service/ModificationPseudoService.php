<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class ModificationPseudoService
{
    public const RESULTAT_OK = 'ok';
    public const RESULTAT_IDENTIQUE = 'identique';
    public const RESULTAT_INDISPONIBLE = 'indisponible';

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function modifier(User $joueur, string $nouveauPseudo): string
    {
        $nouveauPseudo = trim($nouveauPseudo);

        if (strcasecmp($joueur->getPseudo(), $nouveauPseudo) === 0) {
            return self::RESULTAT_IDENTIQUE;
        }

        $utilisateurExistant = $this->userRepository->findOneBy([
            'pseudo' => $nouveauPseudo,
        ]);

        if (
            $utilisateurExistant instanceof User
            && $utilisateurExistant !== $joueur
        ) {
            return self::RESULTAT_INDISPONIBLE;
        }

        $joueur->setPseudo($nouveauPseudo);
        $this->entityManager->flush();

        return self::RESULTAT_OK;
    }
}
