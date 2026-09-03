<?php

namespace App\Entity;

use App\Repository\CombattantCombatRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use LogicException;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CombattantCombatRepository::class)]
#[ORM\UniqueConstraint(
    name: 'UNIQ_COMBAT_JOUEUR_SLOT',
    columns: ['combat_id', 'joueur_id', 'slot']
)]
#[UniqueEntity(
    fields: ['combat', 'joueur', 'slot'],
    message: 'Ce slot est déjà utilisé par ce joueur dans ce combat.'
)]
class CombattantCombat
{
    private const SLOTS_VALIDES = ['A', 'B', 'C', 'D'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'combattants')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Combat $combat = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    private User $joueur;

    #[ORM\Column(length: 1)]
    #[Assert\Choice(
        choices: self::SLOTS_VALIDES,
        message: 'Le slot doit être A, B, C ou D.'
    )]
    private string $slot;

    #[ORM\Column]
    #[Assert\Positive]
    private int $stickmanIdOriginal;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $nomSnapshot;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $imageSnapshot;

    #[ORM\Column]
    #[Assert\Positive]
    private int $rareteSnapshot;

    #[ORM\Column]
    #[Assert\Positive]
    private int $pvMaximum;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $pvActuels;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $attaqueSnapshot;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $defenseSnapshot;

    /**
     * @var list<array<string, mixed>>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $passifsSnapshot = null;

    public function __construct(
        Combat $combat,
        User $joueur,
        string $slot,
        Stickman $stickman,
    ) {
        $slot = strtoupper(trim($slot));

        if (!in_array($slot, self::SLOTS_VALIDES, true)) {
            throw new InvalidArgumentException(
                'Le slot doit être A, B, C ou D.'
            );
        }

        $stickmanId = $stickman->getId();
        $nom = $stickman->getNom();
        $image = $stickman->getImage();
        $rarete = $stickman->getRarete();
        $pv = $stickman->getPv();
        $attaque = $stickman->getAttaque();
        $defense = $stickman->getDefense();

        if (
            $stickmanId === null
            || $nom === null
            || $image === null
            || $rarete === null
            || $pv === null
            || $attaque === null
            || $defense === null
        ) {
            throw new LogicException(
                'Le Stickman doit être enregistré et posséder toutes ses statistiques.'
            );
        }

        if ($rarete <= 0 || $pv <= 0 || $attaque < 0 || $defense < 0) {
            throw new LogicException(
                'Les statistiques du Stickman sont invalides.'
            );
        }

        $this->joueur = $joueur;
        $this->slot = $slot;
        $this->stickmanIdOriginal = $stickmanId;
        $this->nomSnapshot = $nom;
        $this->imageSnapshot = $image;
        $this->rareteSnapshot = $rarete;
        $this->pvMaximum = $pv;
        $this->pvActuels = $pv;
        $this->attaqueSnapshot = $attaque;
        $this->defenseSnapshot = $defense;
        $this->passifsSnapshot = $stickman->getPassifs();

        $combat->addCombattant($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCombat(): ?Combat
    {
        return $this->combat;
    }

    public function setCombat(?Combat $combat): static
    {
        $this->combat = $combat;

        return $this;
    }

    public function getJoueur(): User
    {
        return $this->joueur;
    }

    public function getSlot(): string
    {
        return $this->slot;
    }

    public function getStickmanIdOriginal(): int
    {
        return $this->stickmanIdOriginal;
    }

    public function getNomSnapshot(): string
    {
        return $this->nomSnapshot;
    }

    public function getImageSnapshot(): string
    {
        return $this->imageSnapshot;
    }

    public function getRareteSnapshot(): int
    {
        return $this->rareteSnapshot;
    }

    public function getPvMaximum(): int
    {
        return $this->pvMaximum;
    }

    public function getPvActuels(): int
    {
        return $this->pvActuels;
    }

    public function setPvActuels(int $pvActuels): static
    {
        if ($pvActuels < 0 || $pvActuels > $this->pvMaximum) {
            throw new InvalidArgumentException(
                'Les PV actuels doivent être compris entre 0 et les PV maximum.'
            );
        }

        $this->pvActuels = $pvActuels;

        return $this;
    }

    public function getAttaqueSnapshot(): int
    {
        return $this->attaqueSnapshot;
    }

    public function getDefenseSnapshot(): int
    {
        return $this->defenseSnapshot;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getPassifsSnapshot(): array
    {
        return $this->passifsSnapshot ?? [];
    }

    public function estVivant(): bool
    {
        return $this->pvActuels > 0;
    }

    #[Assert\Callback]
    public function validerCoherence(ExecutionContextInterface $context): void
    {
        if ($this->pvActuels > $this->pvMaximum) {
            $context
                ->buildViolation('Les PV actuels ne peuvent pas dépasser les PV maximum.')
                ->atPath('pvActuels')
                ->addViolation();
        }

        if ($this->combat === null) {
            return;
        }

        $estJoueur1 = $this->joueur === $this->combat->getJoueur1();
        $estJoueur2 = $this->joueur === $this->combat->getJoueur2();

        if (!$estJoueur1 && !$estJoueur2) {
            $context
                ->buildViolation('Ce joueur ne participe pas à ce combat.')
                ->atPath('joueur')
                ->addViolation();
        }
    }
}
