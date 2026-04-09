<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Recolte_archive
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_archive;

    #[ORM\Column(type: "integer")]
    private int $id_recolte_original;

    #[ORM\Column(type: "float")]
    private float $quantite;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_recolte;

    #[ORM\Column(type: "string", length: 100)]
    private string $qualite;

    #[ORM\Column(type: "string", length: 100)]
    private string $type_culture;

    #[ORM\Column(type: "string", length: 100)]
    private string $localisation;

    #[ORM\Column(type: "string", length: 255)]
    private string $cause_supression;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_archivage;

    #[ORM\Column(type: "integer")]
    private int $id_user;

    public function getId_archive()
    {
        return $this->id_archive;
    }

    public function setId_archive($value)
    {
        $this->id_archive = $value;
    }

    public function getId_recolte_original()
    {
        return $this->id_recolte_original;
    }

    public function setId_recolte_original($value)
    {
        $this->id_recolte_original = $value;
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

    public function getCause_supression()
    {
        return $this->cause_supression;
    }

    public function setCause_supression($value)
    {
        $this->cause_supression = $value;
    }

    public function getDate_archivage()
    {
        return $this->date_archivage;
    }

    public function setDate_archivage($value)
    {
        $this->date_archivage = $value;
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
