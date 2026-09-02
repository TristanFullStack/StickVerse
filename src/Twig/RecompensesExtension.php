<?php

namespace App\Twig;

use App\Entity\User;
use App\Service\MissionsJoueurService;
use App\Service\ObjectifJoueurService;
use App\Service\RecompenseHoraireService;
use App\Service\RecompenseQuotidienneService;
use App\Repository\CollectionJeuRepository;
use App\Repository\ClassementSaisonJoueurRepository;
use App\Service\RecompenseClassementSaisonService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class RecompensesExtension extends AbstractExtension
{
    public function __construct(
        private readonly RecompenseQuotidienneService $quotidienne,
        private readonly RecompenseHoraireService $horaire,
        private readonly MissionsJoueurService $missions,
        private readonly ObjectifJoueurService $objectifs,
        private readonly CollectionJeuRepository $collections,
        private readonly ClassementSaisonJoueurRepository $classements,
        private readonly RecompenseClassementSaisonService $recompensesSaison,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('recompenses_disponibles', [$this, 'disponibles'])];
    }

    public function disponibles(?User $joueur): bool
    {
        if (!$joueur instanceof User) {
            return false;
        }
        if ($this->quotidienne->estDisponible($joueur) || $this->horaire->montantDisponible($joueur) > 0) {
            return true;
        }
        foreach ($this->missions->construire($joueur) as $periode) {
            foreach ($periode as $mission) {
                if (($mission['disponible'] ?? false) === true) {
                    return true;
                }
            }
        }
        foreach ($this->objectifs->construire($joueur) as $objectif) {
            if (($objectif['disponible'] ?? false) === true) {
                return true;
            }
        }
        foreach ($this->collections->trouverSaisonsClassees() as $saison) {
            $classement = $this->classements->findOneBy(['joueur' => $joueur, 'saison' => $saison]);
            if ($classement !== null && $this->recompensesSaison->estDisponible($classement)) {
                return true;
            }
        }

        return false;
    }
}
