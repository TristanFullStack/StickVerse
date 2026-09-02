<?php

namespace App\Repository;

use App\Entity\Actualite;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Actualite> */
final class ActualiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, Actualite::class); }

    /** @return list<Actualite> */
    public function trouverPubliees(?DateTimeImmutable $date = null): array
    {
        $date ??= new DateTimeImmutable();
        return $this->createQueryBuilder('actualite')
            ->andWhere('actualite.statutActif = :actif')
            ->andWhere('actualite.datePublication IS NULL OR actualite.datePublication <= :date')
            ->setParameter('actif', true)->setParameter('date', $date)
            ->orderBy('actualite.datePublication', 'DESC')->addOrderBy('actualite.id', 'DESC')
            ->getQuery()->getResult();
    }
}
