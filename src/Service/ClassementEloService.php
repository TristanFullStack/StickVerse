<?php

namespace App\Service;

use App\Entity\ClassementSaisonJoueur;
use App\Entity\Combat;
use App\Entity\User;
use App\Repository\ClassementSaisonJoueurRepository;
use Doctrine\ORM\EntityManagerInterface;

final class ClassementEloService
{
    public const K_FACTOR = 32;
    public const ECART_PUISSANCE_ELO_MAXIMUM = 300;

    public function __construct(
        private readonly ?ScorePuissanceService $scorePuissanceService = null,
        private readonly ?ClassementSaisonJoueurRepository $classementSaisonRepository = null,
        private readonly ?EntityManagerInterface $entityManager = null,
    ) {
    }

    /**
     * @return array{joueur1: int, joueur2: int}
     */
    public function mettreAJourSiNecessaire(Combat $combat): array
    {
        if ($combat->estEloAttribuee()) {
            return [
                'joueur1' => 0,
                'joueur2' => 0,
            ];
        }

        if (
            !$combat->estTermine()
            && !$combat->estAbandonne()
            && !$combat->estForfait()
        ) {
            return [
                'joueur1' => 0,
                'joueur2' => 0,
            ];
        }

        $joueur1 = $combat->getJoueur1();
        $joueur2 = $combat->getJoueur2();

        if (!$joueur2 instanceof User) {
            return [
                'joueur1' => 0,
                'joueur2' => 0,
            ];
        }

        if ($combat->estPrive()) {
            $combat->marquerEloAttribuee();

            return [
                'joueur1' => 0,
                'joueur2' => 0,
            ];
        }

        $scoreJoueur1 = $this->scorePour($combat, $joueur1);
        $servicePuissance = $this->scorePuissanceService
            ?? new ScorePuissanceService();
        $puissanceJoueur1 = $servicePuissance
            ->calculerCombatPourJoueur($combat, $joueur1);
        $puissanceJoueur2 = $servicePuissance
            ->calculerCombatPourJoueur($combat, $joueur2);
        $attenduJoueur1 = $this->scoreAttendu(
            $joueur1->getElo(),
            $joueur2->getElo(),
            $puissanceJoueur1,
            $puissanceJoueur2,
        );
        $variationJoueur1 = $this->calculerVariation(
            $scoreJoueur1,
            $attenduJoueur1,
        );
        $variationJoueur2 = -$variationJoueur1;

        $joueur1->modifierElo($variationJoueur1);
        $joueur2->modifierElo($variationJoueur2);
        $this->mettreAJourClassementSaison(
            $combat,
            $joueur1,
            $joueur2,
            $scoreJoueur1,
            $puissanceJoueur1,
            $puissanceJoueur2,
        );
        $combat->marquerEloAttribuee();

        return [
            'joueur1' => $variationJoueur1,
            'joueur2' => $variationJoueur2,
        ];
    }

    private function calculerVariation(
        float $score,
        float $scoreAttendu,
    ): int {
        return (int) round(
            self::K_FACTOR * ($score - $scoreAttendu),
        );
    }

    private function mettreAJourClassementSaison(
        Combat $combat,
        User $joueur1,
        User $joueur2,
        float $scoreJoueur1,
        int $puissanceJoueur1,
        int $puissanceJoueur2,
    ): void {
        $saison = $combat->getSaisonClassement();

        if (
            $saison === null
            || $this->classementSaisonRepository === null
            || $this->entityManager === null
        ) {
            return;
        }

        $classementJoueur1 = $this->classementSaisonRepository
            ->findOneBy([
                'joueur' => $joueur1,
                'saison' => $saison,
            ]);
        $classementJoueur2 = $this->classementSaisonRepository
            ->findOneBy([
                'joueur' => $joueur2,
                'saison' => $saison,
            ]);

        if (!$classementJoueur1 instanceof ClassementSaisonJoueur) {
            $classementJoueur1 = new ClassementSaisonJoueur(
                $joueur1,
                $saison,
            );
            $this->entityManager->persist($classementJoueur1);
        }

        if (!$classementJoueur2 instanceof ClassementSaisonJoueur) {
            $classementJoueur2 = new ClassementSaisonJoueur(
                $joueur2,
                $saison,
            );
            $this->entityManager->persist($classementJoueur2);
        }

        $attenduSaisonJoueur1 = $this->scoreAttendu(
            $classementJoueur1->getElo(),
            $classementJoueur2->getElo(),
            $puissanceJoueur1,
            $puissanceJoueur2,
        );
        $variationSaisonJoueur1 = $this->calculerVariation(
            $scoreJoueur1,
            $attenduSaisonJoueur1,
        );

        $classementJoueur1->enregistrerResultat(
            $variationSaisonJoueur1,
            $scoreJoueur1,
        );
        $classementJoueur2->enregistrerResultat(
            -$variationSaisonJoueur1,
            1.0 - $scoreJoueur1,
        );
    }

    private function scoreAttendu(
        int $eloJoueur,
        int $eloAdversaire,
        int $puissanceJoueur,
        int $puissanceAdversaire,
    ): float {
        $ecartPuissanceConvertiEnElo = 0.0;
        $puissanceMaximum = max($puissanceJoueur, $puissanceAdversaire);

        if ($puissanceMaximum > 0) {
            $ecartRelatif = (
                $puissanceAdversaire - $puissanceJoueur
            ) / $puissanceMaximum;
            $ecartPuissanceConvertiEnElo = max(
                -self::ECART_PUISSANCE_ELO_MAXIMUM,
                min(
                    self::ECART_PUISSANCE_ELO_MAXIMUM,
                    400 * $ecartRelatif,
                ),
            );
        }

        $ecartEffectif = ($eloAdversaire - $eloJoueur)
            + $ecartPuissanceConvertiEnElo;

        return 1 / (1 + 10 ** ($ecartEffectif / 400));
    }

    private function scorePour(Combat $combat, User $joueur): float
    {
        $gagnant = $combat->getGagnant();

        if ($gagnant === null) {
            return 0.5;
        }

        return $gagnant === $joueur || (
            $gagnant->getId() !== null
            && $gagnant->getId() === $joueur->getId()
        ) ? 1.0 : 0.0;
    }
}
