<?php

namespace App\Repository;

use App\Entity\CollectionJeu;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CollectionJeu>
 */
class CollectionJeuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CollectionJeu::class);
    }

    public function trouverSaisonActive(?DateTimeImmutable $date = null): ?CollectionJeu
    {
        $date ??= new DateTimeImmutable();

        return $this->createQueryBuilder('collection')
            ->andWhere('collection.statutActif = :actif')
            ->andWhere('collection.saison > 0')
            ->andWhere('collection.dateDebut IS NULL OR collection.dateDebut <= :date')
            ->andWhere('collection.dateFin IS NULL OR collection.dateFin >= :date')
            ->setParameter('actif', true)
            ->setParameter('date', $date)
            ->orderBy('collection.saison', 'DESC')
            ->addOrderBy('collection.dateDebut', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<CollectionJeu>
     */
    public function trouverDisponibles(?DateTimeImmutable $date = null): array
    {
        $date ??= new DateTimeImmutable();

        return $this->createQueryBuilder('collection')
            ->andWhere('collection.statutActif = :actif')
            ->andWhere('collection.dateDebut IS NULL OR collection.dateDebut <= :date')
            ->andWhere('collection.dateFin IS NULL OR collection.dateFin >= :date')
            ->setParameter('actif', true)
            ->setParameter('date', $date)
            ->orderBy('collection.saison', 'DESC')
            ->addOrderBy('collection.nom', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
