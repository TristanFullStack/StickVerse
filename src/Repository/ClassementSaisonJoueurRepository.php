<?php

namespace App\Repository;

use App\Entity\ClassementSaisonJoueur;
use App\Entity\CollectionJeu;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
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

    /**
     * @return list<ClassementSaisonJoueur>
     */
    public function trouverPourJoueur(User $joueur): array
    {
        return $this->createQueryBuilder('classement')
            ->addSelect('saison')
            ->innerJoin('classement.saison', 'saison')
            ->andWhere('classement.joueur = :joueur')
            ->setParameter('joueur', $joueur)
            ->orderBy('saison.saison', 'DESC')
            ->addOrderBy('saison.dateFin', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function trouverAvecVerrouEcriture(
        User $joueur,
        CollectionJeu $saison,
    ): ?ClassementSaisonJoueur {
        return $this->createQueryBuilder('classement')
            ->andWhere('classement.joueur = :joueur')
            ->andWhere('classement.saison = :saison')
            ->setParameter('joueur', $joueur)
            ->setParameter('saison', $saison)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }
}
