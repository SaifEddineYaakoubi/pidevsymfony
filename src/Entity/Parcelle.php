<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Utilisateur;
use Doctrine\Common\Collections\Collection;
use App\Entity\Culture;

#[ORM\Entity]
class Parcelle
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_parcelle;

    #[ORM\Column(type: "string", length: 100)]
    private string $nom;

    #[ORM\Column(type: "float")]
    private float $superficie;

    #[ORM\Column(type: "string", length: 150)]
    private string $localisation;

    #[ORM\Column(type: "string", length: 50)]
    private string $etat;

        #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "parcelles")]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', onDelete: 'CASCADE')]
    private Utilisateur $id_user;

    public function getId_parcelle()
    {
        return $this->id_parcelle;
    }

    public function setId_parcelle($value)
    {
        $this->id_parcelle = $value;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($value)
    {
        $this->nom = $value;
    }

    public function getSuperficie()
    {
        return $this->superficie;
    }

    public function setSuperficie($value)
    {
        $this->superficie = $value;
    }

    public function getLocalisation()
    {
        return $this->localisation;
    }

    public function setLocalisation($value)
    {
        $this->localisation = $value;
    }

    public function getEtat()
    {
        return $this->etat;
    }

    public function setEtat($value)
    {
        $this->etat = $value;
    }

    public function getId_user()
    {
        return $this->id_user;
    }

    public function setId_user($value)
    {
        $this->id_user = $value;
    }

    #[ORM\OneToMany(mappedBy: "id_parcelle", targetEntity: Culture::class)]
    private Collection $cultures;
}
