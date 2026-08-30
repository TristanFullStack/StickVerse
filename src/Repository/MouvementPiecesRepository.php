<?php

namespace App\Repository;

use App\Entity\MouvementPieces;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MouvementPieces>
 */
class MouvementPiecesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MouvementPieces::class);
    }

    /**
     * @return list<MouvementPieces>
     */
    public function trouverPourJoueur(
        User $joueur,
        int $limite = 20,
    ): array {
        return $this->createQueryBuilder('mouvement')
            ->andWhere('mouvement.utilisateur = :joueur')
            ->setParameter('joueur', $joueur)
            ->orderBy('mouvement.dateCreation', 'DESC')
            ->addOrderBy('mouvement.id', 'DESC')
            ->setMaxResults(max(1, $limite))
            ->getQuery()
            ->getResult();
    }

    public function compterPourJoueurEtType(User $joueur, string $type): int
    {
        return (int) $this->createQueryBuilder('mouvement')
            ->select('COUNT(mouvement.id)')
            ->andWhere('mouvement.utilisateur = :joueur')
            ->andWhere('mouvement.type = :type')
            ->setParameter('joueur', $joueur)
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
