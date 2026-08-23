<?php

namespace App\Repository;

use App\Entity\Combat;
use App\Entity\ResultatRoundCombat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ResultatRoundCombat>
 */
class ResultatRoundCombatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResultatRoundCombat::class);
    }

    /**
     * @return list<ResultatRoundCombat>
     */
    public function trouverPourCombat(Combat $combat): array
    {
        return $this->findBy(
            [
                'combat' => $combat,
            ],
            [
                'numeroRound' => 'ASC',
            ],
        );
    }
}
