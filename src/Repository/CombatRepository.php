<?php

namespace App\Repository;

use App\Entity\Combat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Combat>
 */
class CombatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Combat::class);
    }

    public function trouverAvecVerrouEcriture(
        int $id,
    ): ?Combat {
        return $this->getEntityManager()->find(
            Combat::class,
            $id,
            LockMode::PESSIMISTIC_WRITE,
        );
    }

    public function trouverActifPourJoueur(
        User $joueur,
    ): ?Combat {
        return $this->createQueryBuilder('combat')
            ->andWhere(
                'combat.joueur1 = :joueur'
                .' OR combat.joueur2 = :joueur'
            )
            ->andWhere('combat.statut IN (:statuts)')
            ->setParameter('joueur', $joueur)
            ->setParameter(
                'statuts',
                [
                    Combat::STATUT_EN_ATTENTE,
                    Combat::STATUT_EN_COURS,
                ],
            )
            ->orderBy('combat.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Combat>
     */
    public function trouverDisponiblesPour(
        User $joueur,
    ): array {
        return $this->createQueryBuilder('combat')
            ->andWhere('combat.statut = :statut')
            ->andWhere('combat.joueur2 IS NULL')
            ->andWhere('combat.joueur1 != :joueur')
            ->setParameter(
                'statut',
                Combat::STATUT_EN_ATTENTE,
            )
            ->setParameter('joueur', $joueur)
            ->orderBy('combat.dateCreation', 'ASC')
            ->addOrderBy('combat.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}