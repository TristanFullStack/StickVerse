<?php

namespace App\Repository;

use App\Entity\Caisse;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Caisse>
 */
class CaisseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Caisse::class);
    }

    /**
     * @return list<Caisse>
     */
    public function trouverDisponibles(?DateTimeImmutable $date = null): array
    {
        $date ??= new DateTimeImmutable();

        return $this->createQueryBuilder('caisse')
            ->leftJoin('caisse.collectionJeu', 'collection')
            ->andWhere('caisse.statutActif = :actif')
            ->andWhere('collection.id IS NULL OR collection.statutActif = :actif')
            ->andWhere('collection.id IS NULL OR collection.dateDebut IS NULL OR collection.dateDebut <= :date')
            ->andWhere('collection.id IS NULL OR collection.dateFin IS NULL OR collection.dateFin >= :date')
            ->setParameter('actif', true)
            ->setParameter('date', $date)
            ->orderBy('caisse.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Caisse[] Returns an array of Caisse objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Caisse
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
