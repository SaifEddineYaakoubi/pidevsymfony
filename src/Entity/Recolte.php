<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Culture;

#[ORM\Entity]
class Recolte
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_recolte;

    #[ORM\Column(type: "float")]
    private float $quantite;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_recolte;

    #[ORM\Column(type: "string", length: 50)]
    private string $qualite;

        #[ORM\ManyToOne(targetEntity: Culture::class, inversedBy: "recoltes")]
    #[ORM\JoinColumn(name: 'id_culture', referencedColumnName: 'id_culture', onDelete: 'CASCADE')]
    private Culture $id_culture;

    #[ORM\Column(type: "string", length: 100)]
    private string $type_culture;

    #[ORM\Column(type: "string", length: 150)]
    private string $localisation;

    #[ORM\Column(type: "integer")]
    private int $id_user;

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
}
