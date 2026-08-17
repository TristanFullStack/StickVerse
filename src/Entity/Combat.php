<?php

namespace App\Entity;

use App\Repository\CombatRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CombatRepository::class)]
class Combat
{
    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_EN_COURS = 'en_cours';
    public const STATUT_TERMINE = 'termine';
    public const STATUT_ABANDONNE = 'abandonne';

    private const STATUTS_VALIDES = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_EN_COURS,
        self::STATUT_TERMINE,
        self::STATUT_ABANDONNE,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(
        message: 'Le combat doit posséder un joueur 1.'
    )]
    private User $joueur1;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $joueur2 = null;

    #[ORM\Column(length: 30)]
    #[Assert\Choice(
        choices: self::STATUTS_VALIDES,
        message: 'Le statut du combat est invalide.'
    )]
    private string $statut = self::STATUT_EN_ATTENTE;

    #[ORM\Column]
    #[Assert\Positive(
        message: 'Le numéro du round doit être supérieur à 0.'
    )]
    private int $numeroRound = 1;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $gagnant = null;

    #[ORM\Column]
    private DateTimeImmutable $dateCreation;

    #[ORM\Column]
    private DateTimeImmutable $dateMiseAJour;

    /**
     * @var Collection<int, CombattantCombat>
     */
    #[ORM\OneToMany(
        targetEntity: CombattantCombat::class,
        mappedBy: 'combat',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $combattants;

    /**
     * @var Collection<int, PlanRoundCombat>
     */
    #[ORM\OneToMany(
        targetEntity: PlanRoundCombat::class,
        mappedBy: 'combat',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $plans;

    public function __construct(User $joueur1)
    {
        $maintenant = new DateTimeImmutable();

        $this->joueur1 = $joueur1;
        $this->dateCreation = $maintenant;
        $this->dateMiseAJour = $maintenant;
        $this->combattants = new ArrayCollection();
        $this->plans = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJoueur1(): User
    {
        return $this->joueur1;
    }

    public function getJoueur2(): ?User
    {
        return $this->joueur2;
    }

    public function setJoueur2(?User $joueur2): static
    {
        $this->joueur2 = $joueur2;
        $this->actualiserDate();

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        if (!in_array($statut, self::STATUTS_VALIDES, true)) {
            throw new InvalidArgumentException(
                'Le statut du combat est invalide.'
            );
        }

        $this->statut = $statut;
        $this->actualiserDate();

        return $this;
    }

    public function getNumeroRound(): int
    {
        return $this->numeroRound;
    }

    public function setNumeroRound(int $numeroRound): static
    {
        if ($numeroRound < 1) {
            throw new InvalidArgumentException(
                'Le numéro du round doit être supérieur à 0.'
            );
        }

        $this->numeroRound = $numeroRound;
        $this->actualiserDate();

        return $this;
    }

    public function passerAuRoundSuivant(): static
    {
        $this->numeroRound++;
        $this->actualiserDate();

        return $this;
    }

    public function getGagnant(): ?User
    {
        return $this->gagnant;
    }

    public function setGagnant(?User $gagnant): static
    {
        $this->gagnant = $gagnant;
        $this->actualiserDate();

        return $this;
    }

    public function getDateCreation(): DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function getDateMiseAJour(): DateTimeImmutable
    {
        return $this->dateMiseAJour;
    }

    public function estParticipant(User $joueur): bool
    {
        return $joueur === $this->joueur1
            || $joueur === $this->joueur2;
    }

    public function estEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function estEnCours(): bool
    {
        return $this->statut === self::STATUT_EN_COURS;
    }

    public function estTermine(): bool
    {
        return $this->statut === self::STATUT_TERMINE;
    }

    #[Assert\Callback]
    public function validerParticipants(
        ExecutionContextInterface $context,
    ): void {
        if (
            $this->joueur2 !== null
            && $this->joueur1 === $this->joueur2
        ) {
            $context
                ->buildViolation(
                    'Un utilisateur ne peut pas jouer contre lui-même.'
                )
                ->atPath('joueur2')
                ->addViolation();
        }

        if (
            $this->gagnant !== null
            && $this->gagnant !== $this->joueur1
            && $this->gagnant !== $this->joueur2
        ) {
            $context
                ->buildViolation(
                    'Le gagnant doit participer au combat.'
                )
                ->atPath('gagnant')
                ->addViolation();
        }
    }

    private function actualiserDate(): void
    {
        $this->dateMiseAJour = new DateTimeImmutable();
    }

    /**
     * @return Collection<int, CombattantCombat>
     */
    public function getCombattants(): Collection
    {
        return $this->combattants;
    }

    public function addCombattant(CombattantCombat $combattant): static
    {
        if (!$this->combattants->contains($combattant)) {
            $this->combattants->add($combattant);
            $combattant->setCombat($this);
        }

        return $this;
    }

    public function removeCombattant(CombattantCombat $combattant): static
    {
        if ($this->combattants->removeElement($combattant)) {
            if ($combattant->getCombat() === $this) {
                $combattant->setCombat(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PlanRoundCombat>
     */
    public function getPlans(): Collection
    {
        return $this->plans;
    }

    public function addPlan(PlanRoundCombat $plan): static
    {
        if ($plan->getCombat() !== $this) {
            throw new InvalidArgumentException(
                'Le plan doit appartenir à ce combat.'
            );
        }

        if (!$this->plans->contains($plan)) {
            $this->plans->add($plan);
        }

        return $this;
    }

    public function removePlan(PlanRoundCombat $plan): static
    {
        $this->plans->removeElement($plan);

        return $this;
    }
}