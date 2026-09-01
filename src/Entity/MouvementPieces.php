<?php

namespace App\Entity;

use App\Repository\MouvementPiecesRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: MouvementPiecesRepository::class)]
class MouvementPieces
{
    public const TYPE_ACHAT_CAISSE = 'achat_caisse';
    public const TYPE_RECOMPENSE_COMBAT = 'recompense_combat';
    public const TYPE_RECOMPENSE_QUOTIDIENNE = 'recompense_quotidienne';
    public const TYPE_RECOMPENSE_OBJECTIF = 'recompense_objectif';
    public const TYPE_RECOMPENSE_SAISON = 'recompense_saison';
    public const TYPE_ADMIN_CREDIT = 'admin_credit';

    private const TYPES_VALIDES = [
        self::TYPE_ACHAT_CAISSE,
        self::TYPE_RECOMPENSE_COMBAT,
        self::TYPE_RECOMPENSE_QUOTIDIENNE,
        self::TYPE_RECOMPENSE_OBJECTIF,
        self::TYPE_RECOMPENSE_SAISON,
        self::TYPE_ADMIN_CREDIT,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'mouvementsPieces')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $utilisateur;

    #[ORM\Column]
    private int $montant;

    #[ORM\Column(length: 30)]
    private string $type;

    #[ORM\Column(length: 255)]
    private string $libelle;

    #[ORM\Column]
    private DateTimeImmutable $dateCreation;

    public function __construct(
        User $utilisateur,
        int $montant,
        string $type,
        string $libelle,
    ) {
        if ($montant === 0) {
            throw new InvalidArgumentException(
                'Un mouvement doit modifier le solde.'
            );
        }

        if (!in_array($type, self::TYPES_VALIDES, true)) {
            throw new InvalidArgumentException(
                'Le type de mouvement est invalide.'
            );
        }

        $this->utilisateur = $utilisateur;
        $this->montant = $montant;
        $this->type = $type;
        $this->libelle = trim($libelle);
        $this->dateCreation = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUtilisateur(): User
    {
        return $this->utilisateur;
    }

    public function getMontant(): int
    {
        return $this->montant;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getLibelle(): string
    {
        return $this->libelle;
    }

    public function getDateCreation(): DateTimeImmutable
    {
        return $this->dateCreation;
    }
}
