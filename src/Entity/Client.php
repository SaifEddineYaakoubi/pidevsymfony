<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\ClientRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Vente;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id_client = null;

    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank(message: "Le nom du client est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Assert\Regex(
        pattern: "/^[a-zA-ZÀ-ÿ\s\-']+$/",
        message: "Le nom ne peut contenir que des lettres, des espaces, des tirets et des apostrophes."
    )]
    private string $nom;

    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank(message: "Le contact est obligatoire.")]
    #[Assert\AtLeastOneOf(
        constraints: [
            new Assert\Email(message: "Le contact doit être une adresse email valide."),
            new Assert\Regex(
                pattern: '/^[0-9+]{1,3}[0-9]{8,}$/',
                message: "Le contact doit être un numéro de téléphone valide (au minimum 8 chiffres) ou une adresse email."
            )
        ],
        message: "Le contact doit être une adresse email valide ou un numéro de téléphone valide."
    )]
    #[Assert\Length(
        min: 8,
        max: 100,
        minMessage: "Le contact doit contenir au moins 8 caractères (Email ou Téléphone)."
    )]
    private string $contact;

    #[ORM\Column(type: "string", length: 150)]
    #[Assert\NotBlank(message: "L'adresse est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 150,
        minMessage: "L'adresse est trop courte. Minimum {{ limit }} caractères requis."
    )]
    private string $adresse;

    #[ORM\Column(type: "integer", nullable: true)]
    private ?int $id_user = null;

    #[ORM\Column(type: "string", length: 20, nullable: true)]
    private ?string $badge = null;

    #[ORM\OneToMany(mappedBy: "id_client", targetEntity: Vente::class, cascade: ["remove"])]
    private Collection $ventes;

    public function __construct()
    {
        $this->ventes = new ArrayCollection();
    }

    // --- Getters & Setters ---

    public function getId_client(): ?int
    {
        return $this->id_client;
    }

    // Pas de setter pour l'id: il est auto-généré par la base

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;
        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }

    public function setContact(string $contact): self
    {
        $this->contact = $contact;
        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(string $adresse): self
    {
        $this->adresse = $adresse;
        return $this;
    }

    public function getId_user(): ?int
    {
        return $this->id_user;
    }

    public function setId_user(?int $id_user): self
    {
        $this->id_user = $id_user;
        return $this;
    }

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function setBadge(?string $badge): self
    {
        $this->badge = $badge;
        return $this;
    }

    /**
     * Retourne l'enum ClientBadge correspondant au badge du client
     */
    public function getBadgeEnum(): \App\Enum\ClientBadge
    {
        return \App\Enum\ClientBadge::tryFrom($this->badge ?? 'none') 
            ?? \App\Enum\ClientBadge::NONE;
    }

    /**
     * @return Collection<int, Vente>
     */
    public function getVentes(): Collection
    {
        return $this->ventes;
    }

    public function addVente(Vente $vente): self
    {
        if (!$this->ventes->contains($vente)) {
            $this->ventes[] = $vente;
            $vente->setId_client($this);
        }
        return $this;
    }

    public function removeVente(Vente $vente): self
    {
        if ($this->ventes->removeElement($vente)) {
            if ($vente->getId_client() === $this) {
                $vente->setId_client(null);
            }
        }
        return $this;
    }
}