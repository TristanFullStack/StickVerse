<?php

namespace App\Entity;

use App\Repository\ReinitialisationMotDePasseRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReinitialisationMotDePasseRepository::class)]
class ReinitialisationMotDePasse
{
    public const DUREE_VALIDITE_SECONDES = 3600;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $utilisateur;

    #[ORM\Column(length: 64, unique: true)]
    private string $jetonHash;

    #[ORM\Column]
    private DateTimeImmutable $dateCreation;

    #[ORM\Column]
    private DateTimeImmutable $dateExpiration;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $dateUtilisation = null;

    public function __construct(
        User $utilisateur,
        string $jeton,
        ?DateTimeImmutable $dateCreation = null,
    ) {
        $this->utilisateur = $utilisateur;
        $this->jetonHash = hash('sha256', $jeton);
        $this->dateCreation = $dateCreation ?? new DateTimeImmutable();
        $this->dateExpiration = $this->dateCreation->modify(
            '+'.self::DUREE_VALIDITE_SECONDES.' seconds'
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): User
    {
        return $this->utilisateur;
    }

    public function getJetonHash(): string
    {
        return $this->jetonHash;
    }

    public function getDateCreation(): DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getDateExpiration(): DateTimeImmutable
    {
        return $this->dateExpiration;
    }

    public function getDateUtilisation(): ?DateTimeImmutable
    {
        return $this->dateUtilisation;
    }

    public function estValide(?DateTimeImmutable $maintenant = null): bool
    {
        $maintenant ??= new DateTimeImmutable();

        return $this->dateUtilisation === null
            && $maintenant < $this->dateExpiration;
    }

    public function utiliser(?DateTimeImmutable $maintenant = null): void
    {
        $this->dateUtilisation = $maintenant ?? new DateTimeImmutable();
    }
}
