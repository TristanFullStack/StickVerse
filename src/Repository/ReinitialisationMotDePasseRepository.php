<?php

namespace App\Repository;

use App\Entity\ReinitialisationMotDePasse;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReinitialisationMotDePasse>
 */
final class ReinitialisationMotDePasseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReinitialisationMotDePasse::class);
    }

    public function trouverValideParJeton(
        string $jeton,
    ): ?ReinitialisationMotDePasse {
        $demande = $this->findOneBy([
            'jetonHash' => hash('sha256', $jeton),
        ]);

        return $demande?->estValide() === true ? $demande : null;
    }

    public function supprimerPour(User $utilisateur): void
    {
        $this->createQueryBuilder('demande')
            ->delete()
            ->andWhere('demande.utilisateur = :utilisateur')
            ->setParameter('utilisateur', $utilisateur)
            ->getQuery()
            ->execute();
    }
}
