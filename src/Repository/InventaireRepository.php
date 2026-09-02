<?php

namespace App\Repository;

use App\Entity\CollectionJeu;
use App\Entity\Inventaire;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Inventaire>
 */
class InventaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Inventaire::class);
    }

    public function compterStickmenDistinctsPourCollection(
        User $joueur,
        CollectionJeu $collection,
    ): int {
        return (int) $this->createQueryBuilder('inventaire')
            ->select('COUNT(DISTINCT stickman.id)')
            ->innerJoin('inventaire.stickman', 'stickman')
            ->andWhere('inventaire.utilisateur = :joueur')
            ->andWhere('inventaire.quantite > 0')
            ->andWhere('stickman.collectionJeu = :collection')
            ->andWhere('stickman.statutActif = :actif')
            ->setParameter('joueur', $joueur)
            ->setParameter('collection', $collection)
            ->setParameter('actif', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

//    /**
//     * @return Inventaire[] Returns an array of Inventaire objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('i.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Inventaire
//    {
//        return $this->createQueryBuilder('i')
//            ->andWhere('i.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
