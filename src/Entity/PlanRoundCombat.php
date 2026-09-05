<?php

namespace App\Entity;

use App\Model\PlanCombat;
use App\Repository\PlanRoundCombatRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: PlanRoundCombatRepository::class)]
#[ORM\UniqueConstraint(
    name: 'UNIQ_PLAN_ROUND_COMBAT_JOUEUR_ROUND',
    fields: ['combat', 'joueur', 'numeroRound']
)]
class PlanRoundCombat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'plans')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Combat $combat;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $joueur;

    #[ORM\Column]
    private int $numeroRound;

    #[ORM\Column(length: 1)]
    private string $cibleAttaqueX;

    #[ORM\Column(length: 1)]
    private string $cibleAttaqueY;

    #[ORM\Column(length: 1)]
    private string $cibleDefenseX;

    #[ORM\Column(length: 1)]
    private string $cibleDefenseY;

    #[ORM\Column]
    private DateTimeImmutable $dateSoumission;

    public function __construct(
        Combat $combat,
        User $joueur,
        PlanCombat $plan,
    ) {
        if (!$combat->estParticipant($joueur)) {
            throw new InvalidArgumentException(
                'Le joueur doit participer au combat pour soumettre un plan.'
            );
        }

        $this->combat = $combat;
        $this->joueur = $joueur;
        $this->numeroRound = $combat->getNumeroRound();
        $this->cibleAttaqueX = $plan->getCibleAttaqueX();
        $this->cibleAttaqueY = $plan->getCibleAttaqueY();
        $this->cibleDefenseX = $plan->getCibleDefenseX();
        $this->cibleDefenseY = $plan->getCibleDefenseY();
        $this->dateSoumission = new DateTimeImmutable();

        $combat->addPlan($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCombat(): Combat
    {
        return $this->combat;
    }

    public function getJoueur(): User
    {
        return $this->joueur;
    }

    public function getNumeroRound(): int
    {
        return $this->numeroRound;
    }

    public function getCibleAttaqueX(): string
    {
        return $this->cibleAttaqueX;
    }

    public function getCibleAttaqueY(): string
    {
        return $this->cibleAttaqueY;
    }

    public function getCibleDefenseX(): string
    {
        return $this->cibleDefenseX;
    }

    public function getCibleDefenseY(): string
    {
        return $this->cibleDefenseY;
    }

    public function getDateSoumission(): DateTimeImmutable
    {
        return $this->dateSoumission;
    }

    public function toPlanCombat(): PlanCombat
    {
        return new PlanCombat(
            $this->cibleAttaqueX,
            $this->cibleAttaqueY,
            $this->cibleDefenseX,
            $this->cibleDefenseY,
        );
    }
}
