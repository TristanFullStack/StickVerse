<?php

namespace App\Repository;

use App\Entity\ClassementSaisonJoueur;
use App\Entity\CollectionJeu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ClassementSaisonJoueur>
 */
class ClassementSaisonJoueurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClassementSaisonJoueur::class);
    }

    /**
     * @return list<ClassementSaisonJoueur>
     */
    public function trouverPourSaison(CollectionJeu $saison): array
    {
        return $this->createQueryBuilder('classement')
            ->addSelect('joueur')
            ->innerJoin('classement.joueur', 'joueur')
            ->andWhere('classement.saison = :saison')
            ->setParameter('saison', $saison)
            ->orderBy('classement.elo', 'DESC')
            ->addOrderBy('classement.victoires', 'DESC')
            ->addOrderBy('classement.parties', 'ASC')
            ->addOrderBy('joueur.pseudo', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
