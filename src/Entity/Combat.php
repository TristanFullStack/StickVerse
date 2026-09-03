<?php

namespace App\Entity;

use App\Repository\CombatRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: CombatRepository::class)]
class Combat
{
    public const DUREE_MAX_ATTENTE_SECONDES = 300;
    public const DUREE_MAX_PREPARATION_SECONDES = 300;
    public const NOMBRE_MAX_ROUNDS = 20;

    public const STATUT_EN_ATTENTE = 'en_attente';
    public const STATUT_EN_COURS = 'en_cours';
    public const STATUT_TERMINE = 'termine';
    public const STATUT_ABANDONNE = 'abandonne';
    public const STATUT_FORFAIT = 'forfait';
    public const STATUT_ANNULE = 'annule';

    private const STATUTS_VALIDES = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_EN_COURS,
        self::STATUT_TERMINE,
        self::STATUT_ABANDONNE,
        self::STATUT_FORFAIT,
        self::STATUT_ANNULE,
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

    #[ORM\Column(nullable: true)]
    private ?int $dernierRoundResolu = null;

    /**
     * @var array<string, array<string, int>>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $derniersResultats = null;

    #[ORM\Column]
    private DateTimeImmutable $dateCreation;

    #[ORM\Column]
    private DateTimeImmutable $dateMiseAJour;

    #[ORM\Column(length: 9, unique: true, nullable: true)]
    private ?string $codeInvitation = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $prive = false;

    #[ORM\Column(nullable: true)]
    private ?bool $joueur1Pret = null;

    #[ORM\Column(nullable: true)]
    private ?bool $joueur2Pret = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $recompenseAttribuee = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $eloAttribuee = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?CollectionJeu $saisonClassement = null;

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

    /**
     * @var Collection<int, ResultatRoundCombat>
     */
    #[ORM\OneToMany(
        targetEntity: ResultatRoundCombat::class,
        mappedBy: 'combat',
        cascade: ['persist'],
        orphanRemoval: true
    )]
    private Collection $resultatsRounds;

    public function __construct(User $joueur1)
    {
        $maintenant = new DateTimeImmutable();

        $this->joueur1 = $joueur1;
        $this->dateCreation = $maintenant;
        $this->dateMiseAJour = $maintenant;
        $this->combattants = new ArrayCollection();
        $this->plans = new ArrayCollection();
        $this->resultatsRounds = new ArrayCollection();
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

    public function getSaisonClassement(): ?CollectionJeu
    {
        return $this->saisonClassement;
    }

    public function setSaisonClassement(
        ?CollectionJeu $saisonClassement,
    ): static {
        if (
            $saisonClassement !== null
            && ($saisonClassement->getSaison() ?? 0) <= 0
        ) {
            throw new InvalidArgumentException(
                'Le combat classé doit appartenir à une saison numérotée.'
            );
        }

        $this->saisonClassement = $saisonClassement;

        return $this;
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

    public function getDernierRoundResolu(): ?int
    {
        return $this->dernierRoundResolu;
    }

    /**
     * @return array<string, array<string, int>>|null
     */
    public function getDerniersResultats(): ?array
    {
        return $this->derniersResultats;
    }

    /**
     * @param array<string, array<string, int>> $resultats
     */
    public function enregistrerResultatsRound(
        int $numeroRound,
        array $resultats,
    ): static {
        if ($numeroRound < 1) {
            throw new InvalidArgumentException(
                'Le numéro du round résolu doit être supérieur à 0.'
            );
        }

        $this->dernierRoundResolu = $numeroRound;
        $this->derniersResultats = $resultats;
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

    public function getCodeInvitation(): ?string
    {
        return $this->codeInvitation;
    }

    public function setCodeInvitation(string $codeInvitation): static
    {
        $codeInvitation = strtoupper(trim($codeInvitation));

        if (preg_match('/^SV-[A-F0-9]{6}$/', $codeInvitation) !== 1) {
            throw new InvalidArgumentException(
                'Le code d’invitation du combat est invalide.'
            );
        }

        $this->codeInvitation = $codeInvitation;

        return $this;
    }

    public function estPrive(): bool
    {
        return $this->prive;
    }

    public function setPrive(bool $prive): static
    {
        $this->prive = $prive;

        return $this;
    }

    public function initialiserPreparation(): static
    {
        if (!$this->joueur2 instanceof User) {
            throw new InvalidArgumentException(
                'Deux joueurs sont nécessaires pour préparer le combat.'
            );
        }

        $this->joueur1Pret = false;
        $this->joueur2Pret = false;
        $this->actualiserDate();

        return $this;
    }

    public function estPreparationInitialisee(): bool
    {
        return $this->joueur1Pret !== null
            && $this->joueur2Pret !== null;
    }

    public function estPretAJouer(): bool
    {
        if (!$this->estPreparationInitialisee()) {
            return true;
        }

        return $this->joueur1Pret === true
            && $this->joueur2Pret === true;
    }

    public function estEnPreparation(): bool
    {
        return $this->estEnCours()
            && $this->estPreparationInitialisee()
            && !$this->estPretAJouer();
    }

    public function getDateExpirationPreparation(): DateTimeImmutable
    {
        return $this->dateMiseAJour->modify(
            '+'.self::DUREE_MAX_PREPARATION_SECONDES.' seconds'
        );
    }

    public function estPreparationExpiree(
        DateTimeImmutable $maintenant,
    ): bool {
        return $this->estEnPreparation()
            && $maintenant >= $this->getDateExpirationPreparation();
    }

    public function estPret(User $joueur): bool
    {
        if (!$this->estPreparationInitialisee()) {
            return $this->estEnCours()
                && $this->estParticipant($joueur);
        }

        if ($this->memeJoueur($joueur, $this->joueur1)) {
            return $this->joueur1Pret === true;
        }

        if (
            $this->joueur2 instanceof User
            && $this->memeJoueur($joueur, $this->joueur2)
        ) {
            return $this->joueur2Pret === true;
        }

        return false;
    }

    public function confirmerPret(User $joueur): static
    {
        if (!$this->estEnCours()) {
            throw new InvalidArgumentException(
                'Seul un combat en cours peut être préparé.'
            );
        }

        if (!$this->estPreparationInitialisee()) {
            throw new InvalidArgumentException(
                'La préparation de ce combat n’est pas initialisée.'
            );
        }

        if ($this->memeJoueur($joueur, $this->joueur1)) {
            if ($this->joueur1Pret === true) {
                return $this;
            }

            $this->joueur1Pret = true;
        } elseif (
            $this->joueur2 instanceof User
            && $this->memeJoueur($joueur, $this->joueur2)
        ) {
            if ($this->joueur2Pret === true) {
                return $this;
            }

            $this->joueur2Pret = true;
        } else {
            throw new InvalidArgumentException(
                'Seul un participant peut confirmer sa préparation.'
            );
        }

        $this->actualiserDate();

        return $this;
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

    public function estAnnule(): bool
    {
        return $this->statut === self::STATUT_ANNULE;
    }

    public function estForfait(): bool
    {
        return $this->statut === self::STATUT_FORFAIT;
    }

    public function estAbandonne(): bool
    {
        return $this->statut === self::STATUT_ABANDONNE;
    }

    public function estRecompenseAttribuee(): bool
    {
        return $this->recompenseAttribuee;
    }

    public function marquerRecompenseAttribuee(): static
    {
        $this->recompenseAttribuee = true;
        $this->actualiserDate();

        return $this;
    }

    public function estEloAttribuee(): bool
    {
        return $this->eloAttribuee ?? false;
    }

    public function marquerEloAttribuee(): static
    {
        $this->eloAttribuee = true;
        $this->actualiserDate();

        return $this;
    }

    public function getDateExpirationAttente(): DateTimeImmutable
    {
        return $this->dateCreation->modify(
            '+'.self::DUREE_MAX_ATTENTE_SECONDES.' seconds'
        );
    }

    public function estAttenteExpiree(
        DateTimeImmutable $maintenant,
    ): bool {
        return $this->estEnAttente()
            && $this->joueur2 === null
            && $maintenant >= $this->getDateExpirationAttente();
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

    private function memeJoueur(User $premier, User $second): bool
    {
        if ($premier === $second) {
            return true;
        }

        return $premier->getId() !== null
            && $premier->getId() === $second->getId();
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

    /**
     * @return Collection<int, ResultatRoundCombat>
     */
    public function getResultatsRounds(): Collection
    {
        return $this->resultatsRounds;
    }

    public function addResultatRound(
        ResultatRoundCombat $resultatRound,
    ): static {
        if ($resultatRound->getCombat() !== $this) {
            throw new InvalidArgumentException(
                'Le résultat doit appartenir à ce combat.'
            );
        }

        if (!$this->resultatsRounds->contains($resultatRound)) {
            $this->resultatsRounds->add($resultatRound);
        }

        return $this;
    }

    public function removeResultatRound(
        ResultatRoundCombat $resultatRound,
    ): static {
        $this->resultatsRounds->removeElement($resultatRound);

        return $this;
    }
}
