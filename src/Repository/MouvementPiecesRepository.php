<?php

namespace App\Repository;

use App\Entity\MouvementPieces;
use App\Entity\User;
use DateTimeImmutable;
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

    public function compterDepuisPourJoueurEtType(
        User $joueur,
        string $type,
        DateTimeImmutable $debut,
    ): int {
        return (int) $this->createQueryBuilder('mouvement')
            ->select('COUNT(mouvement.id)')
            ->andWhere('mouvement.utilisateur = :joueur')
            ->andWhere('mouvement.type = :type')
            ->andWhere('mouvement.dateCreation >= :debut')
            ->setParameter('joueur', $joueur)
            ->setParameter('type', $type)
            ->setParameter('debut', $debut)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return list<MouvementPieces> */
    public function trouverRecompenses(int $limite = 100): array
    {
        return $this->createQueryBuilder('mouvement')
            ->addSelect('utilisateur')
            ->innerJoin('mouvement.utilisateur', 'utilisateur')
            ->andWhere('mouvement.montant > 0')
            ->andWhere('mouvement.type LIKE :type')
            ->setParameter('type', 'recompense%')
            ->orderBy('mouvement.dateCreation', 'DESC')
            ->addOrderBy('mouvement.id', 'DESC')
            ->setMaxResults(max(1, $limite))
            ->getQuery()->getResult();
    }
}
