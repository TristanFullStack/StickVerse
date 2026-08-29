<?php

namespace App\Service;

use App\Entity\Inventaire;
use App\Entity\Stickman;
use App\Entity\User;
use App\Repository\StickmanRepository;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;

final class InitialisationNouveauJoueurService
{
    /**
     * Les slugs font partie du catalogue versionné et constituent
     * l'équipe de départ garantie de chaque nouveau joueur.
     *
     * @var list<string>
     */
    public const STICKMANS_DEPART = [
        'guerrier',
        'archer',
        'lancier',
        'tank',
    ];

    public function __construct(
        private readonly StickmanRepository $stickmanRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<Stickman>
     */
    public function initialiser(User $utilisateur): array
    {
        $stickmans = $this->stickmanRepository->findBy([
            'slug' => self::STICKMANS_DEPART,
            'statutActif' => true,
        ]);

        $stickmansParSlug = [];

        foreach ($stickmans as $stickman) {
            $slug = $stickman->getSlug();

            if (is_string($slug)) {
                $stickmansParSlug[$slug] = $stickman;
            }
        }

        $slugsManquants = array_values(array_diff(
            self::STICKMANS_DEPART,
            array_keys($stickmansParSlug),
        ));

        if ($slugsManquants !== []) {
            throw new LogicException(sprintf(
                'Le pack de départ est incomplet. Stickmans actifs manquants : %s.',
                implode(', ', $slugsManquants),
            ));
        }

        $slugsDejaPossedes = [];

        foreach ($utilisateur->getInventaires() as $inventaire) {
            $slug = $inventaire->getStickman()?->getSlug();

            if (is_string($slug)) {
                $slugsDejaPossedes[$slug] = true;
            }
        }

        $packDepart = [];

        foreach (self::STICKMANS_DEPART as $slug) {
            $stickman = $stickmansParSlug[$slug];
            $packDepart[] = $stickman;

            if (isset($slugsDejaPossedes[$slug])) {
                continue;
            }

            $inventaire = (new Inventaire())
                ->setUtilisateur($utilisateur)
                ->setStickman($stickman);

            $utilisateur->addInventaire($inventaire);
            $this->entityManager->persist($inventaire);
        }

        return $packDepart;
    }
}
