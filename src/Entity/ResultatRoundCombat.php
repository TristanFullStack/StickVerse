<?php

namespace App\Entity;

use App\Repository\ResultatRoundCombatRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: ResultatRoundCombatRepository::class)]
#[ORM\UniqueConstraint(
    name: 'UNIQ_RESULTAT_ROUND_COMBAT_COMBAT_ROUND',
    fields: ['combat', 'numeroRound']
)]
class ResultatRoundCombat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'resultatsRounds')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Combat $combat;

    #[ORM\Column]
    private int $numeroRound;

    /**
     * @var array<string, array<string, int>>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $resultats;

    #[ORM\Column]
    private DateTimeImmutable $dateResolution;

    /**
     * @param array<string, array<string, int>> $resultats
     */
    public function __construct(
        Combat $combat,
        int $numeroRound,
        array $resultats,
    ) {
        if ($numeroRound < 1) {
            throw new InvalidArgumentException(
                'Le numéro du round résolu doit être supérieur à 0.'
            );
        }

        if ($resultats === []) {
            throw new InvalidArgumentException(
                'Le résultat du round ne peut pas être vide.'
            );
        }

        $this->combat = $combat;
        $this->numeroRound = $numeroRound;
        $this->resultats = $resultats;
        $this->dateResolution = new DateTimeImmutable();

        $combat->addResultatRound($this);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCombat(): Combat
    {
        return $this->combat;
    }

    public function getNumeroRound(): int
    {
        return $this->numeroRound;
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function getResultats(): array
    {
        return $this->resultats;
    }

    public function getDateResolution(): DateTimeImmutable
    {
        return $this->dateResolution;
    }
}
