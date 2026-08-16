<?php

namespace App\Entity;

use App\Repository\InventaireRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InventaireRepository::class)]
#[ORM\UniqueConstraint(
    name: 'UNIQ_UTILISATEUR_STICKMAN',
    columns: ['utilisateur_id', 'stickman_id']
)]
#[UniqueEntity(
    fields: ['utilisateur', 'stickman'],
    message: 'Ce Stickman est déjà présent dans cet inventaire.'
)]
class Inventaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\Positive(message: 'La quantité doit être supérieure à 0.')]
    private ?int $quantite = 1;

    #[ORM\ManyToOne(inversedBy: 'inventaires')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $utilisateur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stickman $stickman = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): static
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getUtilisateur(): ?User
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?User $utilisateur): static
    {
        $this->utilisateur = $utilisateur;

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