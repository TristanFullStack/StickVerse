<?php

namespace App\Entity;

use App\Repository\StickmanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: StickmanRepository::class)]
class Stickman
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire')]
    private ?string $nom = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Le champ est obligatoire.')]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'Le champ est obligatoire.')]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le champ est obligatoire.')]
    private ?string $image = null;

    #[ORM\Column]
    #[Assert\Range(min:1, max:5, notInRangeMessage: 'La valeur doit être entre 1 et 5.')]
    private ?int $rarete = null;

    #[ORM\Column]
    #[Assert\Positive(message: 'Le nombre doit être supérieur à 0.')]
    private ?int $pv = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Le nombre doit être supérieur ou égal à 0.')]
    private ?int $attaque = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Le nombre doit être supérieur ou égal à 0.')]
    private ?int $defense = null;

    #[ORM\Column]
    private ?bool $statutActif = null;

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
        $this->nom = $nom;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getRarete(): ?int
    {
        return $this->rarete;
    }

    public function setRarete(int $rarete): static
    {
        $this->rarete = $rarete;

        return $this;
    }

    public function getPv(): ?int
    {
        return $this->pv;
    }

    public function setPv(int $pv): static
    {
        $this->pv = $pv;

        return $this;
    }

    public function getAttaque(): ?int
    {
        return $this->attaque;
    }

    public function setAttaque(int $attaque): static
    {
        $this->attaque = $attaque;

        return $this;
    }

    public function getDefense(): ?int
    {
        return $this->defense;
    }

    public function setDefense(int $defense): static
    {
        $this->defense = $defense;

        return $this;
    }

    public function isStatutActif(): ?bool
    {
        return $this->statutActif;
    }

    public function setStatutActif(bool $statutActif): static
    {
        $this->statutActif = $statutActif;

        return $this;
    }
}
