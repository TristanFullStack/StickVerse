<?php

namespace App\Entity;

use App\Repository\CaissePossedeeRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une ligne représente une caisse physique dans l'inventaire.
 * Les lignes ne sont volontairement pas regroupées afin de rendre visible
 * chaque caisse reçue ou achetée.
 */
#[ORM\Entity(repositoryClass: CaissePossedeeRepository::class)]
class CaissePossedee
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'caissesPossedees')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $utilisateur = null;

    #[ORM\ManyToOne(inversedBy: 'possessions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Caisse $caisse = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $dateAcquisition;

    public function __construct(?User $utilisateur = null, ?Caisse $caisse = null)
    {
        $this->utilisateur = $utilisateur;
        $this->caisse = $caisse;
        $this->dateAcquisition = new DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getUtilisateur(): ?User { return $this->utilisateur; }
    public function setUtilisateur(?User $utilisateur): static { $this->utilisateur = $utilisateur; return $this; }
    public function getCaisse(): ?Caisse { return $this->caisse; }
    public function setCaisse(?Caisse $caisse): static { $this->caisse = $caisse; return $this; }
    public function getDateAcquisition(): DateTimeImmutable { return $this->dateAcquisition; }
}
