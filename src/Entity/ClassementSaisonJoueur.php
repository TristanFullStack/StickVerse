<?php

namespace App\Entity;

use App\Repository\ClassementSaisonJoueurRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: ClassementSaisonJoueurRepository::class)]
#[ORM\UniqueConstraint(
    name: 'UNIQ_CLASSEMENT_SAISON_JOUEUR',
    fields: ['joueur', 'saison'],
)]
class ClassementSaisonJoueur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $joueur;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CollectionJeu $saison;

    #[ORM\Column(options: ['unsigned' => true, 'default' => User::ELO_DEPART])]
    private int $elo = User::ELO_DEPART;

    #[ORM\Column(options: ['unsigned' => true, 'default' => 0])]
    private int $parties = 0;

    #[ORM\Column(options: ['unsigned' => true, 'default' => 0])]
    private int $victoires = 0;

    #[ORM\Column(options: ['unsigned' => true, 'default' => 0])]
    private int $defaites = 0;

    #[ORM\Column(options: ['unsigned' => true, 'default' => 0])]
    private int $matchsNuls = 0;

    #[ORM\Column]
    private DateTimeImmutable $dateMiseAJour;

    #[ORM\Column(options: ['default' => false])]
    private bool $recompenseReclamee = false;

    #[ORM\Column(nullable: true)]
    private ?DateTimeImmutable $dateRecompenseReclamee = null;

    public function __construct(User $joueur, CollectionJeu $saison)
    {
        if (($saison->getSaison() ?? 0) <= 0) {
            throw new InvalidArgumentException(
                'Le classement doit appartenir à une saison numérotée.'
            );
        }

        $this->joueur = $joueur;
        $this->saison = $saison;
        $this->dateMiseAJour = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJoueur(): User
    {
        return $this->joueur;
    }

    public function getSaison(): CollectionJeu
    {
        return $this->saison;
    }

    public function getElo(): int
    {
        return $this->elo;
    }

    public function getParties(): int
    {
        return $this->parties;
    }

    public function getVictoires(): int
    {
        return $this->victoires;
    }

    public function getDefaites(): int
    {
        return $this->defaites;
    }

    public function getMatchsNuls(): int
    {
        return $this->matchsNuls;
    }

    public function getDateMiseAJour(): DateTimeImmutable
    {
        return $this->dateMiseAJour;
    }

    public function estRecompenseReclamee(): bool
    {
        return $this->recompenseReclamee;
    }

    public function getDateRecompenseReclamee(): ?DateTimeImmutable
    {
        return $this->dateRecompenseReclamee;
    }

    public function marquerRecompenseReclamee(
        ?DateTimeImmutable $date = null,
    ): static {
        $this->recompenseReclamee = true;
        $this->dateRecompenseReclamee = $date ?? new DateTimeImmutable();

        return $this;
    }

    public function enregistrerResultat(
        int $variationElo,
        float $score,
    ): static {
        if (!in_array($score, [0.0, 0.5, 1.0], true)) {
            throw new InvalidArgumentException(
                'Le score saisonnier doit valoir 0, 0,5 ou 1.'
            );
        }

        $this->elo = max(0, $this->elo + $variationElo);
        $this->parties++;

        if ($score === 1.0) {
            $this->victoires++;
        } elseif ($score === 0.5) {
            $this->matchsNuls++;
        } else {
            $this->defaites++;
        }

        $this->dateMiseAJour = new DateTimeImmutable();

        return $this;
    }
}
