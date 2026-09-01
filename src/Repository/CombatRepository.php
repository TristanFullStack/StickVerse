<?php

namespace App\Repository;

use App\Entity\Combat;
use App\Entity\User;
use DateTimeImmutable;
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

    public function trouverParCodeInvitation(string $code): ?Combat
    {
        return $this->findOneBy([
            'codeInvitation' => strtoupper(trim($code)),
        ]);
    }

    public function codeInvitationExiste(string $code): bool
    {
        return $this->count([
            'codeInvitation' => strtoupper(trim($code)),
        ]) > 0;
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
     * @return list<int>
     */
    public function trouverIdsActifs(): array
    {
        $resultats = $this->createQueryBuilder('combat')
            ->select('combat.id')
            ->andWhere('combat.statut IN (:statuts)')
            ->setParameter(
                'statuts',
                [
                    Combat::STATUT_EN_ATTENTE,
                    Combat::STATUT_EN_COURS,
                ],
            )
            ->orderBy('combat.id', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_map(
            static fn (array $resultat): int =>
                (int) $resultat['id'],
            $resultats,
        );
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
            ->andWhere('combat.prive = :prive')
            ->setParameter(
                'statut',
                Combat::STATUT_EN_ATTENTE,
            )
            ->setParameter('joueur', $joueur)
            ->setParameter('prive', false)
            ->orderBy('combat.dateCreation', 'ASC')
            ->addOrderBy('combat.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Combat>
     */
    public function trouverHistoriquePourJoueur(
        User $joueur,
        int $limite = 10,
    ): array {
        return $this->createQueryBuilder('combat')
            ->addSelect('joueur1', 'joueur2', 'gagnant')
            ->innerJoin('combat.joueur1', 'joueur1')
            ->innerJoin('combat.joueur2', 'joueur2')
            ->leftJoin('combat.gagnant', 'gagnant')
            ->andWhere(
                'combat.joueur1 = :joueur'
                .' OR combat.joueur2 = :joueur'
            )
            ->andWhere('combat.statut IN (:statuts)')
            ->setParameter('joueur', $joueur)
            ->setParameter(
                'statuts',
                [
                    Combat::STATUT_TERMINE,
                    Combat::STATUT_ABANDONNE,
                    Combat::STATUT_FORFAIT,
                ],
            )
            ->orderBy('combat.dateMiseAJour', 'DESC')
            ->addOrderBy('combat.id', 'DESC')
            ->setMaxResults(max(1, $limite))
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{
     *     total: int,
     *     victoires: int,
     *     defaites: int,
     *     matchsNuls: int
     * }
     */
    public function calculerStatistiquesPourJoueur(
        User $joueur,
    ): array {
        $resultat = $this->createQueryBuilder('combat')
            ->select('COUNT(combat.id) AS total')
            ->addSelect(
                'SUM(CASE WHEN combat.gagnant = :joueur'
                .' THEN 1 ELSE 0 END) AS victoires'
            )
            ->addSelect(
                'SUM(CASE WHEN combat.statut = :termine'
                .' AND combat.gagnant IS NULL'
                .' THEN 1 ELSE 0 END) AS matchsNuls'
            )
            ->andWhere(
                'combat.joueur1 = :joueur'
                .' OR combat.joueur2 = :joueur'
            )
            ->andWhere('combat.statut IN (:statuts)')
            ->setParameter('joueur', $joueur)
            ->setParameter('termine', Combat::STATUT_TERMINE)
            ->setParameter(
                'statuts',
                [
                    Combat::STATUT_TERMINE,
                    Combat::STATUT_ABANDONNE,
                    Combat::STATUT_FORFAIT,
                ],
            )
            ->getQuery()
            ->getSingleResult();

        $total = (int) $resultat['total'];
        $victoires = (int) $resultat['victoires'];
        $matchsNuls = (int) $resultat['matchsNuls'];

        return [
            'total' => $total,
            'victoires' => $victoires,
            'defaites' => max(
                0,
                $total - $victoires - $matchsNuls,
            ),
            'matchsNuls' => $matchsNuls,
        ];
    }

    public function compterDepuisPourJoueur(
        User $joueur,
        DateTimeImmutable $debut,
    ): int {
        return (int) $this->createQueryBuilder('combat')
            ->select('COUNT(combat.id)')
            ->andWhere('(combat.joueur1 = :joueur OR combat.joueur2 = :joueur)')
            ->andWhere('combat.statut IN (:statuts)')
            ->andWhere('combat.dateMiseAJour >= :debut')
            ->setParameter('joueur', $joueur)
            ->setParameter('statuts', [
                Combat::STATUT_TERMINE,
                Combat::STATUT_ABANDONNE,
                Combat::STATUT_FORFAIT,
            ])
            ->setParameter('debut', $debut)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function compterVictoiresDepuisPourJoueur(
        User $joueur,
        DateTimeImmutable $debut,
    ): int {
        return (int) $this->createQueryBuilder('combat')
            ->select('COUNT(combat.id)')
            ->andWhere('(combat.joueur1 = :joueur OR combat.joueur2 = :joueur)')
            ->andWhere('combat.gagnant = :joueur')
            ->andWhere('combat.dateMiseAJour >= :debut')
            ->setParameter('joueur', $joueur)
            ->setParameter('debut', $debut)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
