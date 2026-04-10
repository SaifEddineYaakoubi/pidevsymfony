<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Culture;

#[ORM\Entity]
class Recolte
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_recolte;

    #[ORM\Column(type: "float")]
    #[Assert\NotNull(message: 'La quantité est obligatoire.')]
    #[Assert\Positive(message: 'La quantité doit être strictement supérieure à 0.')]
    private float $quantite;

    #[ORM\Column(type: "date", nullable: true)]
    #[Assert\NotNull(message: 'La date de récolte est obligatoire.')]
    #[Assert\LessThanOrEqual('today', message: 'La date de récolte ne peut pas être dans le futur.')]
    private ?\DateTimeInterface $date_recolte;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\NotBlank(message: 'La qualité est obligatoire.')]
    #[Assert\Choice(choices: ['excellente','bonne','moyenne','mauvaise'], message: 'Qualité invalide.')]
    private ?string $qualite = null;

        #[ORM\ManyToOne(targetEntity: Culture::class, inversedBy: "recoltes")]
    #[ORM\JoinColumn(name: 'id_culture', referencedColumnName: 'id_culture', nullable: true, onDelete: 'CASCADE')]
    private ?Culture $id_culture;

    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank(message: 'Le type de culture est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 100,
        minMessage: 'Le type de culture doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le type de culture ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $type_culture = null;

    #[ORM\Column(type: "string", length: 150)]
    #[Assert\NotBlank(message: 'La localisation est obligatoire.')]
    #[Assert\Length(
        min: 2,
        max: 150,
        minMessage: 'La localisation doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'La localisation ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $localisation = null;

    #[ORM\Column(type: "integer")]
    #[Assert\NotNull(message: 'L\'utilisateur est obligatoire.')]
    #[Assert\Positive(message: 'L\'ID utilisateur doit être un entier positif.')]
    private int $id_user;

    public function __construct()
    {
        $this->quantite = 0.0;
        $this->date_recolte = null;
        $this->qualite = '';
        $this->id_culture = null;
        $this->type_culture = '';
        $this->localisation = '';
        $this->id_user = 0;
    }

    public function getId_recolte()
    {
        return $this->id_recolte;
    }

    public function setId_recolte($value)
    {
        $this->id_recolte = $value;
    }

    public function getQuantite()
    {
        return $this->quantite;
    }

    public function setQuantite($value)
    {
        $this->quantite = $value;
    }

    public function getDate_recolte()
    {
        return $this->date_recolte;
    }

    public function setDate_recolte($value)
    {
        $this->date_recolte = $value;
    }

    public function getQualite()
    {
        return $this->qualite;
    }

    public function setQualite($value)
    {
        $this->qualite = $value;
    }

    public function getId_culture()
    {
        return $this->id_culture;
    }

    public function setId_culture($value)
    {
        $this->id_culture = $value;
    }

    public function getType_culture()
    {
        return $this->type_culture;
    }

    public function setType_culture($value)
    {
        $this->type_culture = $value;
    }

    public function getLocalisation()
    {
        return $this->localisation;
    }

    public function setLocalisation($value)
    {
        $this->localisation = $value;
    }

    public function getId_user()
    {
        return $this->id_user;
    }

    public function setId_user($value)
    {
        $this->id_user = $value;
    }

    public function getDateRecolte()
    {
        return $this->getDate_recolte();
    }

    public function setDateRecolte($value)
    {
        $this->setDate_recolte($value);

        return $this;
    }

    public function getTypeCulture()
    {
        return $this->getType_culture();
    }

    public function setTypeCulture($value)
    {
        $this->setType_culture($value);

        return $this;
    }

    public function getIdRecolte()
    {
        return $this->getId_recolte();
    }

    public function setIdRecolte($value)
    {
        $this->setId_recolte($value);

        return $this;
    }

    public function getIdCulture()
    {
        return $this->getId_culture();
    }

    public function setIdCulture($value)
    {
        $this->setId_culture($value);

        return $this;
    }

    public function getIdUser()
    {
        return $this->getId_user();
    }

    public function setIdUser($value)
    {
        $this->setId_user($value);

        return $this;
    }
}
