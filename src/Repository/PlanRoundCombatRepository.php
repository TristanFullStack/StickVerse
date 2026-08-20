<?php

namespace App\Repository;

use App\Entity\Combat;
use App\Entity\PlanRoundCombat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PlanRoundCombat>
 */
class PlanRoundCombatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PlanRoundCombat::class);
    }

    /**
     * @return list<PlanRoundCombat>
     */
    public function trouverPourCombatEtRound(
        Combat $combat,
        int $numeroRound,
    ): array {
        return $this->createQueryBuilder('plan')
            ->andWhere('plan.combat = :combat')
            ->andWhere('plan.numeroRound = :numeroRound')
            ->setParameter('combat', $combat)
            ->setParameter('numeroRound', $numeroRound)
            ->orderBy('plan.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}