<?php

namespace App\Entity;

use App\Repository\OuvertureCaisseRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OuvertureCaisseRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_OUVERTURE_CAISSE_JETON', columns: ['jeton'])]
class OuvertureCaisse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 64, unique: true)]
    private string $jeton;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $utilisateur;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Caisse $caisse;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Stickman $stickman;

    #[ORM\Column]
    private int $quantiteApres;

    #[ORM\Column]
    private bool $nouveau;

    #[ORM\Column]
    private int $collectionPossedes;

    #[ORM\Column]
    private int $collectionTotal;

    #[ORM\Column]
    private DateTimeImmutable $dateCreation;

    public function __construct(
        string $jeton,
        User $utilisateur,
        Caisse $caisse,
        Stickman $stickman,
        int $quantiteApres,
        bool $nouveau,
        int $collectionPossedes,
        int $collectionTotal,
    ) {
        $this->jeton = $jeton;
        $this->utilisateur = $utilisateur;
        $this->caisse = $caisse;
        $this->stickman = $stickman;
        $this->quantiteApres = $quantiteApres;
        $this->nouveau = $nouveau;
        $this->collectionPossedes = $collectionPossedes;
        $this->collectionTotal = $collectionTotal;
        $this->dateCreation = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJeton(): string
    {
        return $this->jeton;
    }

    public function getUtilisateur(): User
    {
        return $this->utilisateur;
    }

    public function getCaisse(): ?Caisse
    {
        return $this->caisse;
    }

    public function getStickman(): ?Stickman
    {
        return $this->stickman;
    }

    public function getQuantiteApres(): int
    {
        return $this->quantiteApres;
    }

    public function isNouveau(): bool
    {
        return $this->nouveau;
    }

    public function getCollectionPossedes(): int
    {
        return $this->collectionPossedes;
    }

    public function getCollectionTotal(): int
    {
        return $this->collectionTotal;
    }

    public function getDateCreation(): DateTimeImmutable
    {
        return $this->dateCreation;
    }
}
