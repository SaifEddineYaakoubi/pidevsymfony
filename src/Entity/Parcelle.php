<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Utilisateur;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Culture;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Parcelle
{
    public const MAX_SUPERFICIE = 1000000.0;
    /**
     * Stored values (slugs) for parcelle state.
     * Display labels are handled in Twig/Form.
     */
    public const ETATS = ['active', 'repos', 'exploitee'];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id_parcelle = null;

    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank(message: 'Le nom est obligatoire.')]
    private ?string $nom = null;

    #[ORM\Column(type: "float")]
    #[Assert\NotNull(message: 'La superficie est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La superficie ne peut pas être négative.')]
    #[Assert\LessThanOrEqual(value: self::MAX_SUPERFICIE, message: 'La superficie dépasse la borne maximale.')]
    private ?float $superficie = null;

    #[ORM\Column(type: "string", length: 150)]
    #[Assert\NotBlank(message: 'La localisation est obligatoire.')]
    private ?string $localisation = null;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\NotBlank(message: 'L\'état est obligatoire.')]
    #[Assert\Choice(choices: self::ETATS, message: 'État de parcelle invalide.')]
    private ?string $etat = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "parcelles")]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', nullable: false)]
    private ?Utilisateur $id_user = null;

    #[ORM\OneToMany(mappedBy: "id_parcelle", targetEntity: Culture::class)]
    private Collection $cultures;

    public function __construct()
    {
        $this->cultures = new ArrayCollection();
    }

    public function getId_parcelle(): ?int
    {
        return $this->id_parcelle;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $value): self
    {
        $this->nom = $value;

        return $this;
    }

    public function getSuperficie(): ?float
    {
        return $this->superficie;
    }

    public function setSuperficie(?float $value): self
    {
        $this->superficie = $value;

        return $this;
    }

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $value): self
    {
        $this->localisation = $value;

        return $this;
    }

    public function getEtat(): ?string
    {
        return $this->etat;
    }

    public function setEtat(?string $value): self
    {
        $this->etat = $value;

        return $this;
    }

    public function getId_user(): ?Utilisateur
    {
        return $this->id_user;
    }

    public function setId_user(?Utilisateur $value): self
    {
        $this->id_user = $value;

        return $this;
    }

    /** @return Collection<int, Culture> */
    public function getCultures(): Collection
    {
        return $this->cultures;
    }
}
