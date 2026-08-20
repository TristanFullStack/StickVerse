<?php

namespace App\Repository;

use App\Entity\Combat;
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

    public function trouverAvecVerrouEcriture(int $id): ?Combat
    {
        return $this->getEntityManager()->find(
            Combat::class,
            $id,
            LockMode::PESSIMISTIC_WRITE,
        );
    }
}