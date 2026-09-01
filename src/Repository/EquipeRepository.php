<?php

namespace App\Repository;

use App\Entity\Equipe;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Equipe>
 */
class EquipeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipe::class);
    }

    public function nomExistePourUtilisateur(
        User $utilisateur,
        string $nom,
        ?Equipe $equipeIgnoree = null,
    ): bool {
        $constructeur = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.utilisateur = :utilisateur')
            ->andWhere('LOWER(e.nom) = LOWER(:nom)')
            ->setParameter('utilisateur', $utilisateur)
            ->setParameter('nom', trim($nom));

        if ($equipeIgnoree?->getId() !== null) {
            $constructeur
                ->andWhere('e.id != :equipeIgnoree')
                ->setParameter('equipeIgnoree', $equipeIgnoree->getId());
        }

        return (int) $constructeur
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    //    /**
    //     * @return Equipe[] Returns an array of Equipe objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Equipe
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
