<?php

namespace App\Repository;

use App\Entity\Passif;
use App\Entity\Stickman;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Passif> */
final class PassifRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Passif::class);
    }

    /** @return list<Passif> */
    public function trouverActifs(): array
    {
        return $this->findBy(['statutActif' => true], ['nom' => 'ASC']);
    }

    /**
     * Retrouve les entités centrales correspondant aux snapshots historiques
     * d'une carte (les anciens snapshots ne possèdent pas encore d'identifiant).
     *
     * @return list<Passif>
     */
    public function trouverPourStickman(Stickman $stickman): array
    {
        $passifs = $stickman->getPassifs();
        if ($passifs === []) {
            return [];
        }

        $resultat = [];
        foreach ($passifs as $snapshot) {
            if (!is_array($snapshot)) {
                continue;
            }
            $passif = null;
            if (isset($snapshot['id']) && is_numeric($snapshot['id'])) {
                $passif = $this->find((int) $snapshot['id']);
            }
            if (!$passif && isset($snapshot['type'], $snapshot['nom'])) {
                $passif = $this->findOneBy([
                    'type' => (string) $snapshot['type'],
                    'nom' => (string) $snapshot['nom'],
                ]);
            }
            if ($passif instanceof Passif && !in_array($passif, $resultat, true)) {
                $resultat[] = $passif;
            }
        }

        return $resultat;
    }
}
