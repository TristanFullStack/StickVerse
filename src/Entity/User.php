<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use InvalidArgumentException;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_PSEUDO', fields: ['pseudo'])]
#[ORM\UniqueConstraint(
    name: 'UNIQ_USER_VERIFICATION_EMAIL_HASH',
    fields: ['jetonVerificationEmailHash'],
)]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['pseudo'], message: 'Ce pseudo est déjà utilisé.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const PIECES_DEPART = 1000;
    public const ELO_DEPART = 500;
    public const CAISSES_PREMIERS_RENFORTS_DEPART = 5;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $emailVerifie = true;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $jetonVerificationEmailHash = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateExpirationVerificationEmail = null;

    #[ORM\Column(length: 24)]
    private string $pseudo;

    #[ORM\Column(options: ['unsigned' => true, 'default' => self::PIECES_DEPART])]
    private int $pieces = self::PIECES_DEPART;

    #[ORM\Column(options: ['unsigned' => true, 'default' => self::ELO_DEPART])]
    private int $elo = self::ELO_DEPART;

    #[ORM\Column(options: ['unsigned' => true, 'default' => self::CAISSES_PREMIERS_RENFORTS_DEPART])]
    private int $caissesPremiersRenforts = self::CAISSES_PREMIERS_RENFORTS_DEPART;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateDerniereRecompenseQuotidienne = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateDerniereRecompenseHoraire = null;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $objectifsReclames = [];

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, Inventaire>
     */
    #[ORM\OneToMany(targetEntity: Inventaire::class, mappedBy: 'utilisateur', orphanRemoval: true)]
    private Collection $inventaires;

    /**
     * @var Collection<int, Equipe>
     */
    #[ORM\OneToMany(targetEntity: Equipe::class, mappedBy: 'utilisateur', orphanRemoval: true)]
    private Collection $equipes;

    /**
     * @var Collection<int, MouvementPieces>
     */
    #[ORM\OneToMany(
        targetEntity: MouvementPieces::class,
        mappedBy: 'utilisateur',
        orphanRemoval: true,
    )]
    private Collection $mouvementsPieces;

    /**
     * @var Collection<int, CaissePossedee>
     */
    #[ORM\OneToMany(targetEntity: CaissePossedee::class, mappedBy: 'utilisateur', orphanRemoval: true)]
    private Collection $caissesPossedees;

    public function __construct()
    {
        $this->inventaires = new ArrayCollection();
        $this->equipes = new ArrayCollection();
        $this->mouvementsPieces = new ArrayCollection();
        $this->caissesPossedees = new ArrayCollection();
        $this->pseudo = 'Joueur-'.strtoupper(bin2hex(random_bytes(4)));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function isEmailVerifie(): bool
    {
        return $this->emailVerifie;
    }

    public function setEmailVerifie(bool $emailVerifie): static
    {
        $this->emailVerifie = $emailVerifie;

        return $this;
    }

    public function preparerVerificationEmail(
        string $jeton,
        \DateTimeImmutable $expiration,
    ): static {
        $this->emailVerifie = false;
        $this->jetonVerificationEmailHash = hash('sha256', $jeton);
        $this->dateExpirationVerificationEmail = $expiration;

        return $this;
    }

    public function getJetonVerificationEmailHash(): ?string
    {
        return $this->jetonVerificationEmailHash;
    }

    public function getDateExpirationVerificationEmail(): ?\DateTimeImmutable
    {
        return $this->dateExpirationVerificationEmail;
    }

    public function confirmerEmail(): static
    {
        $this->emailVerifie = true;
        $this->jetonVerificationEmailHash = null;
        $this->dateExpirationVerificationEmail = null;

        return $this;
    }

    public function getPseudo(): string
    {
        return $this->pseudo;
    }

    public function setPseudo(string $pseudo): static
    {
        $this->pseudo = trim($pseudo);

        return $this;
    }

    public function getPieces(): int
    {
        return $this->pieces;
    }

    public function setPieces(int $pieces): static
    {
        if ($pieces < 0) {
            throw new InvalidArgumentException(
                'Le nombre de pièces ne peut pas être négatif.'
            );
        }

        $this->pieces = $pieces;

        return $this;
    }

    public function getElo(): int
    {
        return $this->elo ?? self::ELO_DEPART;
    }

    public function setElo(int $elo): static
    {
        if ($elo < 0) {
            throw new InvalidArgumentException(
                'La cote ELO ne peut pas être négative.'
            );
        }

        $this->elo = $elo;

        return $this;
    }

    public function getCaissesPremiersRenforts(): int
    {
        return $this->caissesPremiersRenforts;
    }

    public function setCaissesPremiersRenforts(int $quantite): static
    {
        if ($quantite < 0) {
            throw new InvalidArgumentException(
                'Le nombre de caisses offertes ne peut pas être négatif.'
            );
        }

        $this->caissesPremiersRenforts = $quantite;

        return $this;
    }

    public function consommerCaissePremiersRenforts(): bool
    {
        if ($this->caissesPremiersRenforts <= 0) {
            return false;
        }

        --$this->caissesPremiersRenforts;

        return true;
    }

    public function modifierElo(int $variation): static
    {
        return $this->setElo(max(0, $this->getElo() + $variation));
    }

    public function getDateDerniereRecompenseQuotidienne(): ?\DateTimeImmutable
    {
        return $this->dateDerniereRecompenseQuotidienne;
    }

    public function setDateDerniereRecompenseQuotidienne(
        ?\DateTimeImmutable $date,
    ): static {
        $this->dateDerniereRecompenseQuotidienne = $date;

        return $this;
    }

    public function getDateDerniereRecompenseHoraire(): ?\DateTimeImmutable
    {
        return $this->dateDerniereRecompenseHoraire;
    }

    public function setDateDerniereRecompenseHoraire(
        ?\DateTimeImmutable $date,
    ): static {
        $this->dateDerniereRecompenseHoraire = $date;

        return $this;
    }

    /** @return list<string> */
    public function getObjectifsReclames(): array
    {
        return $this->objectifsReclames ?? [];
    }

    public function aReclameObjectif(string $objectif): bool
    {
        return in_array($objectif, $this->getObjectifsReclames(), true);
    }

    public function marquerObjectifReclame(string $objectif): static
    {
        if (!$this->aReclameObjectif($objectif)) {
            $this->objectifsReclames = [
                ...$this->getObjectifsReclames(),
                $objectif,
            ];
        }

        return $this;
    }

    public function reinitialiserObjectifsReclames(): static
    {
        $this->objectifsReclames = [];

        return $this;
    }

    public function crediterPieces(int $montant): static
    {
        if ($montant <= 0) {
            throw new InvalidArgumentException(
                'Le nombre de pièces crédité doit être positif.'
            );
        }

        $this->pieces += $montant;

        return $this;
    }

    public function debiterPieces(int $montant): bool
    {
        if ($montant < 0) {
            throw new InvalidArgumentException(
                'Le nombre de pièces débité ne peut pas être négatif.'
            );
        }

        if ($this->pieces < $montant) {
            return false;
        }

        $this->pieces -= $montant;

        return true;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    /**
     * @return Collection<int, Inventaire>
     */
    public function getInventaires(): Collection
    {
        return $this->inventaires;
    }

    public function addInventaire(Inventaire $inventaire): static
    {
        if (!$this->inventaires->contains($inventaire)) {
            $this->inventaires->add($inventaire);
            $inventaire->setUtilisateur($this);
        }

        return $this;
    }

    public function removeInventaire(Inventaire $inventaire): static
    {
        if ($this->inventaires->removeElement($inventaire)) {
            // set the owning side to null (unless already changed)
            if ($inventaire->getUtilisateur() === $this) {
                $inventaire->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Equipe>
     */
    public function getEquipes(): Collection
    {
        return $this->equipes;
    }

    public function addEquipe(Equipe $equipe): static
    {
        if (!$this->equipes->contains($equipe)) {
            $this->equipes->add($equipe);
            $equipe->setUtilisateur($this);
        }

        return $this;
    }

    public function removeEquipe(Equipe $equipe): static
    {
        if ($this->equipes->removeElement($equipe)) {
            // set the owning side to null (unless already changed)
            if ($equipe->getUtilisateur() === $this) {
                $equipe->setUtilisateur(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, MouvementPieces>
     */
    public function getMouvementsPieces(): Collection
    {
        return $this->mouvementsPieces;
    }

    public function addMouvementPieces(
        MouvementPieces $mouvement,
    ): static {
        if (!$this->mouvementsPieces->contains($mouvement)) {
            $this->mouvementsPieces->add($mouvement);
        }

        return $this;
    }

    public function removeMouvementPieces(
        MouvementPieces $mouvement,
    ): static {
        $this->mouvementsPieces->removeElement($mouvement);

        return $this;
    }

    /** @return Collection<int, CaissePossedee> */
    public function getCaissesPossedees(): Collection
    {
        return $this->caissesPossedees;
    }

    public function addCaissePossedee(CaissePossedee $caisse): static
    {
        if (!$this->caissesPossedees->contains($caisse)) {
            $this->caissesPossedees->add($caisse);
            $caisse->setUtilisateur($this);
        }

        return $this;
    }

    public function removeCaissePossedee(CaissePossedee $caisse): static
    {
        $this->caissesPossedees->removeElement($caisse);

        return $this;
    }
}
