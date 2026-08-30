<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final readonly class ModificationMotDePasseService
{
    public const RESULTAT_OK = 'ok';
    public const RESULTAT_ACTUEL_INVALIDE = 'actuel_invalide';
    public const RESULTAT_IDENTIQUE = 'identique';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function modifier(
        User $utilisateur,
        string $motDePasseActuel,
        string $nouveauMotDePasse,
    ): string {
        if (!$this->passwordHasher->isPasswordValid(
            $utilisateur,
            $motDePasseActuel,
        )) {
            return self::RESULTAT_ACTUEL_INVALIDE;
        }

        if ($this->passwordHasher->isPasswordValid(
            $utilisateur,
            $nouveauMotDePasse,
        )) {
            return self::RESULTAT_IDENTIQUE;
        }

        $utilisateur->setPassword(
            $this->passwordHasher->hashPassword(
                $utilisateur,
                $nouveauMotDePasse,
            )
        );

        $this->entityManager->flush();

        return self::RESULTAT_OK;
    }
}
