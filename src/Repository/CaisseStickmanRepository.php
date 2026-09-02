<?php

namespace App\Repository;

use App\Entity\CaisseStickman;
use App\Entity\Stickman;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CaisseStickman>
 */
class CaisseStickmanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CaisseStickman::class);
    }

    /** @return list<CaisseStickman> */
    public function trouverDisponiblesPourStickman(Stickman $stickman, ?DateTimeImmutable $date = null): array
    {
        $date ??= new DateTimeImmutable();

        return $this->createQueryBuilder('contenu')
            ->innerJoin('contenu.caisse', 'caisse')->addSelect('caisse')
            ->leftJoin('caisse.collectionJeu', 'collection')->addSelect('collection')
            ->andWhere('contenu.stickman = :stickman')
            ->andWhere('caisse.statutActif = :actif')
            ->andWhere('collection.id IS NULL OR collection.statutActif = :actif')
            ->andWhere('collection.id IS NULL OR collection.dateDebut IS NULL OR collection.dateDebut <= :date')
            ->andWhere('collection.id IS NULL OR collection.dateFin IS NULL OR collection.dateFin >= :date')
            ->setParameter('stickman', $stickman)->setParameter('actif', true)->setParameter('date', $date)
            ->orderBy('caisse.nom', 'ASC')->getQuery()->getResult();
    }

    //    /**
    //     * @return CaisseStickman[] Returns an array of CaisseStickman objects
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

    //    public function findOneBySomeField($value): ?CaisseStickman
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
