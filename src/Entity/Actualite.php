<?php

namespace App\Entity;

use App\Repository\ActualiteRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActualiteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Actualite
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $titre = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private ?string $contenu = null;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $datePublication = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $statutActif = true;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CollectionJeu $saison = null;

    public function getId(): ?int { return $this->id; }
    public function getTitre(): ?string { return $this->titre; }
    public function setTitre(string $titre): static { $this->titre = $titre; return $this; }
    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getContenu(): ?string { return $this->contenu; }
    public function setContenu(string $contenu): static { $this->contenu = $contenu; return $this; }
    public function getDatePublication(): ?DateTimeImmutable { return $this->datePublication; }
    public function setDatePublication(?DateTimeImmutable $datePublication): static { $this->datePublication = $datePublication; return $this; }
    public function isStatutActif(): bool { return $this->statutActif; }
    public function setStatutActif(bool $statutActif): static { $this->statutActif = $statutActif; return $this; }
    public function getSaison(): ?CollectionJeu { return $this->saison; }
    public function setSaison(?CollectionJeu $saison): static { $this->saison = $saison; return $this; }
}
