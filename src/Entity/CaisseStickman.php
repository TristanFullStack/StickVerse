<?php

namespace App\Entity;

use App\Repository\CaisseStickmanRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: CaisseStickmanRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_CAISSE_STICKMAN', columns: ['caisse_id', 'stickman_id'])]
#[UniqueEntity(fields: ['caisse', 'stickman'], message: 'Ce Stickman est déjà présent dans cette caisse.')]

class CaisseStickman
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\Positive(message: 'Le poids doit être supérieur à 0.')]
    private ?int $poids = null;

    #[ORM\ManyToOne(inversedBy: 'contenus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Caisse $caisse = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stickman $stickman = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPoids(): ?int
    {
        return $this->poids;
    }

    public function setPoids(int $poids): static
    {
        $this->poids = $poids;

        return $this;
    }
    
    public function getProbabilite(): float
    {
        $poidsTotal = $this->caisse?->getPoidsTotal() ?? 0;

        if ($poidsTotal === 0) {
            return 0;
        }

        return (($this->poids ?? 0) / $poidsTotal) * 100;
    }
    
    public function getCaisse(): ?Caisse
    {
        return $this->caisse;
    }

    public function setCaisse(?Caisse $caisse): static
    {
        $this->caisse = $caisse;

        return $this;
    }

    public function getStickman(): ?Stickman
    {
        return $this->stickman;
    }

    public function setStickman(?Stickman $stickman): static
    {
        $this->stickman = $stickman;

        return $this;
    }
}
