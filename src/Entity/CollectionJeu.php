<?php

namespace App\Entity;

use App\Repository\CollectionJeuRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CollectionJeuRepository::class)]
class CollectionJeu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de la collection est obligatoire.')]
    private ?string $nom = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Le slug de la collection est obligatoire.')]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description de la collection est obligatoire.')]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\PositiveOrZero(message: 'Le numéro de saison doit être positif ou égal à 0.')]
    private ?int $saison = null;

    #[ORM\Column]
    private ?bool $statutActif = true;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $dateDebut = null;

    #[ORM\Column(nullable: true)]
    #[Assert\GreaterThan(propertyPath: 'dateDebut', message: 'La date de fin doit être postérieure à la date de début.')]
    private ?DateTimeImmutable $dateFin = null;

    /**
     * @var Collection<int, Stickman>
     */
    #[ORM\OneToMany(targetEntity: Stickman::class, mappedBy: 'collectionJeu')]
    private Collection $stickmen;

    /**
     * @var Collection<int, Caisse>
     */
    #[ORM\OneToMany(targetEntity: Caisse::class, mappedBy: 'collectionJeu')]
    private Collection $caisses;

    public function __construct()
    {
        $this->stickmen = new ArrayCollection();
        $this->caisses = new ArrayCollection();
    }

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

    public function getSaison(): ?int
    {
        return $this->saison;
    }

    public function setSaison(int $saison): static
    {
        $this->saison = $saison;

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

    public function getDateDebut(): ?DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(?DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function estDisponibleA(DateTimeImmutable $date): bool
    {
        if (!$this->statutActif) {
            return false;
        }

        if ($this->dateDebut !== null && $date < $this->dateDebut) {
            return false;
        }

        return $this->dateFin === null || $date <= $this->dateFin;
    }

    /**
     * @return Collection<int, Stickman>
     */
    public function getStickmen(): Collection
    {
        return $this->stickmen;
    }

    /**
     * @return Collection<int, Caisse>
     */
    public function getCaisses(): Collection
    {
        return $this->caisses;
    }
}
