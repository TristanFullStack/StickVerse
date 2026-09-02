<?php

namespace App\Repository;

use App\Entity\Caisse;
use App\Entity\CaissePossedee;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CaissePossedee> */
class CaissePossedeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CaissePossedee::class);
    }

    /** @return list<CaissePossedee> */
    public function trouverPourJoueur(User $joueur): array
    {
        return $this->createQueryBuilder('possession')
            ->innerJoin('possession.caisse', 'caisse')->addSelect('caisse')
            ->andWhere('possession.utilisateur = :joueur')
            ->setParameter('joueur', $joueur)
            ->orderBy('possession.dateAcquisition', 'ASC')
            ->addOrderBy('possession.id', 'ASC')
            ->getQuery()->getResult();
    }

    public function trouverPremierePourJoueurEtCaisse(User $joueur, Caisse $caisse): ?CaissePossedee
    {
        return $this->findOneBy(
            ['utilisateur' => $joueur, 'caisse' => $caisse],
            ['dateAcquisition' => 'ASC', 'id' => 'ASC'],
        );
    }

    public function compterPourJoueur(User $joueur): int
    {
        return (int) $this->createQueryBuilder('possession')
            ->select('COUNT(possession.id)')
            ->andWhere('possession.utilisateur = :joueur')
            ->setParameter('joueur', $joueur)
            ->getQuery()->getSingleScalarResult();
    }

    public function compterPourJoueurEtCaisse(User $joueur, Caisse $caisse): int
    {
        return (int) $this->createQueryBuilder('possession')
            ->select('COUNT(possession.id)')
            ->andWhere('possession.utilisateur = :joueur')
            ->andWhere('possession.caisse = :caisse')
            ->setParameter('joueur', $joueur)
            ->setParameter('caisse', $caisse)
            ->getQuery()->getSingleScalarResult();
    }
}
