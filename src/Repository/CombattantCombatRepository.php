<?php

namespace App\Repository;

use App\Entity\Combat;
use App\Entity\CombattantCombat;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CombattantCombat>
 */
class CombattantCombatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CombattantCombat::class);
    }

    /**
     * @return list<CombattantCombat>
     */
    public function trouverPourCombatEtJoueur(
        Combat $combat,
        User $joueur,
    ): array {
        return $this->findBy(
            [
                'combat' => $combat,
                'joueur' => $joueur,
            ],
            [
                'slot' => 'ASC',
            ],
        );
    }
}