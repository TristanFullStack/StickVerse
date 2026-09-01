<?php

namespace App\Entity;

use App\Repository\EquipeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EquipeRepository::class)]
class Equipe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de l’équipe est obligatoire.')]
    #[Assert\Length(
        max: 80,
        maxMessage: 'Le nom de l’équipe ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $nom = null;

    #[ORM\ManyToOne(inversedBy: 'equipes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $utilisateur = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stickman $stickmanA = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stickman $stickmanB = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stickman $stickmanC = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stickman $stickmanD = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = trim($nom);

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

    public function getStickmanA(): ?Stickman
    {
        return $this->stickmanA;
    }

    public function setStickmanA(?Stickman $stickmanA): static
    {
        $this->stickmanA = $stickmanA;

        return $this;
    }

    public function getStickmanB(): ?Stickman
    {
        return $this->stickmanB;
    }

    public function setStickmanB(?Stickman $stickmanB): static
    {
        $this->stickmanB = $stickmanB;

        return $this;
    }

    public function getStickmanC(): ?Stickman
    {
        return $this->stickmanC;
    }

    public function setStickmanC(?Stickman $stickmanC): static
    {
        $this->stickmanC = $stickmanC;

        return $this;
    }

    public function getStickmanD(): ?Stickman
    {
        return $this->stickmanD;
    }

    public function setStickmanD(?Stickman $stickmanD): static
    {
        $this->stickmanD = $stickmanD;

        return $this;
    }
}
