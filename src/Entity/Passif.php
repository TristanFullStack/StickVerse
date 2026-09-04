<?php

namespace App\Entity;

use App\Repository\PassifRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PassifRepository::class)]
#[UniqueEntity(fields: ['slug'], message: 'Ce slug de passif est déjà utilisé.')]
class Passif
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'Le nom du passif est obligatoire.')]
    #[Assert\Length(max: 120)]
    private ?string $nom = null;

    #[ORM\Column(length: 120, unique: true)]
    #[Assert\NotBlank(message: 'Le slug du passif est obligatoire.')]
    #[Assert\Length(max: 120)]
    #[Assert\Regex(pattern: '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', message: 'Utilise uniquement des minuscules, chiffres et tirets.')]
    private ?string $slug = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: 'La description du passif est obligatoire.')]
    private ?string $description = null;

    #[ORM\Column(length: 80)]
    #[Assert\NotBlank(message: 'Le type technique est obligatoire.')]
    private ?string $type = null;

    #[ORM\Column]
    #[Assert\Range(min: 0, max: 50, notInRangeMessage: 'La valeur doit être comprise entre {{ min }} et {{ max }}.')]
    private int $valeur = 0;

    #[ORM\Column(options: ['default' => 0])]
    #[Assert\Range(min: 0, max: 500, notInRangeMessage: 'La puissance doit être comprise entre {{ min }} et {{ max }}.')]
    private int $puissance = 0;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'Le round de départ doit être positif.')]
    private ?int $aPartirRound = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $statutActif = true;

    public function getId(): ?int { return $this->id; }
    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): static { $this->nom = $nom; return $this; }
    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(string $slug): static { $this->slug = $slug; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getValeur(): int { return $this->valeur; }
    public function setValeur(int $valeur): static { $this->valeur = $valeur; return $this; }
    public function getPuissance(): int { return $this->puissance; }
    public function setPuissance(int $puissance): static { $this->puissance = $puissance; return $this; }
    public function getAPartirRound(): ?int { return $this->aPartirRound; }
    public function setAPartirRound(?int $round): static { $this->aPartirRound = $round; return $this; }
    public function isStatutActif(): bool { return $this->statutActif; }
    public function setStatutActif(bool $statutActif): static { $this->statutActif = $statutActif; return $this; }

    /** @return array<string, int|string|bool> */
    public function versTableau(): array
    {
        $tableau = [
            'nom' => (string) $this->nom,
            'description' => (string) $this->description,
            'type' => (string) $this->type,
            'valeur' => $this->valeur,
            'puissance' => $this->puissance,
            'actif' => $this->statutActif,
        ];

        if ($this->id !== null) {
            $tableau['id'] = $this->id;
        }
        if ($this->aPartirRound !== null && $this->aPartirRound > 1) {
            $tableau['a_partir_round'] = $this->aPartirRound;
        }

        return $tableau;
    }
}
