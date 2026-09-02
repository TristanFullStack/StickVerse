<?php

namespace App\Repository;

use App\Entity\CollectionJeu;
use App\Entity\Stickman;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stickman>
 */
class StickmanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stickman::class);
    }

    public function compterActifsPourCollection(CollectionJeu $collection): int
    {
        return $this->count([
            'collectionJeu' => $collection,
            'statutActif' => true,
        ]);
    }

    /**
     * @return list<Stickman>
     */
    public function trouverDisponibles(?DateTimeImmutable $date = null): array
    {
        $date ??= new DateTimeImmutable();

        return $this->createQueryBuilder('stickman')
            ->leftJoin('stickman.collectionJeu', 'collection')
            ->andWhere('stickman.statutActif = :actif')
            ->andWhere('collection.id IS NULL OR collection.statutActif = :actif')
            ->andWhere('collection.id IS NULL OR collection.dateDebut IS NULL OR collection.dateDebut <= :date')
            ->andWhere('collection.id IS NULL OR collection.dateFin IS NULL OR collection.dateFin >= :date')
            ->setParameter('actif', true)
            ->setParameter('date', $date)
            ->orderBy('stickman.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

//    /**
//     * @return Stickman[] Returns an array of Stickman objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Stickman
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
