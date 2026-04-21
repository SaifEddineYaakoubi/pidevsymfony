<?php
// src/Entity/Utilisateur.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\UtilisateurRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\\Repository\\UtilisateurRepository')]
class Utilisateur implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id_user", type: "integer")]
    private ?int $id_user = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le prénom est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'Format d\'email invalide.')]
    #[Assert\Length(max: 255, maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères.')]
    private ?string $email = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le rôle est obligatoire.')]
    #[Assert\Choice(
        choices: ['admin', 'responsable_stock', 'agriculteur'],
        message: 'Rôle invalide.'
    )]
    private ?string $role = null;

    #[ORM\Column(length: 255)]
    private ?string $mot_de_passe = null;

    #[ORM\Column(type: "boolean")]
    private ?bool $statut = null;

    #[ORM\Column(type: "datetime")]
    private ?\DateTimeInterface $date_creation = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $faceDescriptor = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $faceEnabled = false;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $profilePicture = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $sexe = null;



    /**
     * Inverse side for Parcelle::id_user
     * @var Collection<int, Parcelle>
     */
    #[ORM\OneToMany(mappedBy: 'id_user', targetEntity: Parcelle::class, orphanRemoval: false)]
    private Collection $parcelles;

    /**
     * Inverse side for Vente::id_user
     * @var Collection<int, Vente>
     */
    #[ORM\OneToMany(mappedBy: 'id_user', targetEntity: Vente::class, orphanRemoval: false)]
    private Collection $ventes;

    public function __construct()
    {
        $this->parcelles = new ArrayCollection();
        $this->ventes = new ArrayCollection();
    }

    // Getters et Setters existants...

    // === Méthodes requises par UserInterface ===

    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): self
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->mot_de_passe;
    }

    public function setMotDePasse(string $mot_de_passe): self
    {
        $this->mot_de_passe = $mot_de_passe;
        return $this;
    }

    public function getStatut(): ?bool
    {
        return $this->statut;
    }

    public function setStatut(bool $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->date_creation;
    }

    public function setDateCreation(\DateTimeInterface $date_creation): self
    {
        $this->date_creation = $date_creation;
        return $this;
    }



    // === UserInterface Methods ===

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        // Support both short role identifiers (e.g. 'admin') and full Symfony roles (e.g. 'ROLE_ADMIN').
        $role = $this->role ?? '';

        // If the role is already a full ROLE_* string, return it plus ROLE_USER
        if (str_starts_with($role, 'ROLE_')) {
            return [$role, 'ROLE_USER'];
        }

        // Map short role names to Symfony roles
        return match (strtolower($role)) {
            'admin' => ['ROLE_ADMIN', 'ROLE_USER'],
            'responsable_stock' => ['ROLE_STOCK', 'ROLE_USER'],
            'agriculteur' => ['ROLE_AGRICULTEUR', 'ROLE_USER'],
            default => ['ROLE_USER'],
        };
    }

    public function getPassword(): string
    {
        return $this->mot_de_passe;
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    /** @return Collection<int, Parcelle> */
    public function getParcelles(): Collection
    {
        return $this->parcelles;
    }

    /** @return Collection<int, Vente> */
    public function getVentes(): Collection
    {
        return $this->ventes;
    }

    public function getFaceDescriptor(): ?string
    {
        return $this->faceDescriptor;
    }

    public function setFaceDescriptor(?string $faceDescriptor): self
    {
        $this->faceDescriptor = $faceDescriptor;
        return $this;
    }

    public function isFaceEnabled(): ?bool
    {
        return $this->faceEnabled;
    }

    public function setFaceEnabled(?bool $faceEnabled): self
    {
        $this->faceEnabled = $faceEnabled;
        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): self
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): self
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getSexe(): ?string
    {
        return $this->sexe;
    }

    public function setSexe(?string $sexe): self
    {
        $this->sexe = $sexe;
        return $this;
    }

    public function getAge(): ?int
    {
        if (!$this->dateNaissance) {
            return null;
        }
        
        $now = new \DateTime();
        $interval = $this->dateNaissance->diff($now);
        return $interval->y;
    }
}