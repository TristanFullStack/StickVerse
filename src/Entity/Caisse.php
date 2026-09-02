<?php

namespace App\Entity;

use App\Repository\CaisseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CaisseRepository::class)]
class Caisse
{
    public const SLUG_PREMIERS_RENFORTS = 'caisse-saison-1-premiers-renforts';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column]
    private ?int $prix = null;

    #[ORM\Column]
    private ?bool $statutActif = null;

    #[ORM\ManyToOne(inversedBy: 'caisses')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CollectionJeu $collectionJeu = null;

    /**
     * @var Collection<int, CaisseStickman>
     */
    #[ORM\OneToMany(targetEntity: CaisseStickman::class, mappedBy: 'caisse', orphanRemoval: true)]
    private Collection $contenus;

    /** @var Collection<int, CaissePossedee> */
    #[ORM\OneToMany(targetEntity: CaissePossedee::class, mappedBy: 'caisse')]
    private Collection $possessions;

    public function __construct()
    {
        $this->contenus = new ArrayCollection();
        $this->possessions = new ArrayCollection();
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

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): static
    {
        $this->image = $image;

        return $this;
    }

    public function getPrix(): ?int
    {
        return $this->prix;
    }

    public function setPrix(int $prix): static
    {
        $this->prix = $prix;

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

    public function getCollectionJeu(): ?CollectionJeu
    {
        return $this->collectionJeu;
    }

    public function setCollectionJeu(?CollectionJeu $collectionJeu): static
    {
        $this->collectionJeu = $collectionJeu;

        return $this;
    }

    /**
     * @return Collection<int, CaisseStickman>
     */
    public function getContenus(): Collection
    {
        return $this->contenus;
    }

    public function getPoidsTotal(): int
    {
        $total = 0;

        foreach ($this->contenus as $contenu) {
            $total += $contenu->getPoids() ?? 0;
        }

        return $total;
    }

    public function addContenu(CaisseStickman $contenu): static
    {
        if (!$this->contenus->contains($contenu)) {
            $this->contenus->add($contenu);
            $contenu->setCaisse($this);
        }

        return $this;
    }

    public function removeContenu(CaisseStickman $contenu): static
    {
        if ($this->contenus->removeElement($contenu)) {
            // set the owning side to null (unless already changed)
            if ($contenu->getCaisse() === $this) {
                $contenu->setCaisse(null);
            }
        }

        return $this;
    }

    /** @return Collection<int, CaissePossedee> */
    public function getPossessions(): Collection
    {
        return $this->possessions;
    }
}
