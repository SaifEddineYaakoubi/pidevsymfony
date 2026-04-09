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
    private int $id_client;

    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank(message: "Le nom du client est obligatoire.")]
    #[Assert\Length(
        min: 3,
        max: 100,
        minMessage: "Le nom doit contenir au moins {{ limit }} caractères.",
        maxMessage: "Le nom ne peut pas dépasser {{ limit }} caractères."
    )]
    private string $nom;

    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank(message: "Le contact est obligatoire.")]
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

    #[ORM\OneToMany(mappedBy: "id_client", targetEntity: Vente::class)]
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

    public function setId_client(int $id_client): self
    {
        $this->id_client = $id_client;
        return $this;
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