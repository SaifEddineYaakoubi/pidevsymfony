<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Rendement
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_rendement;

    #[ORM\Column(type: "float")]
    private float $surface_exploitee;

    #[ORM\Column(type: "float")]
    private float $quantite_totale;

    #[ORM\Column(type: "float")]
    private float $productivite;

    #[ORM\Column(type: "integer")]
    private int $id_recolte;

    public function getId_rendement()
    {
        return $this->id_rendement;
    }

    public function setId_rendement($value)
    {
        $this->id_rendement = $value;
    }

    public function getSurface_exploitee()
    {
        return $this->surface_exploitee;
    }

    public function setSurface_exploitee($value)
    {
        $this->surface_exploitee = $value;
    }

    public function getQuantite_totale()
    {
        return $this->quantite_totale;
    }

    public function setQuantite_totale($value)
    {
        $this->quantite_totale = $value;
    }

    public function getProductivite()
    {
        return $this->productivite;
    }

    public function setProductivite($value)
    {
        $this->productivite = $value;
    }

    public function getId_recolte()
    {
        return $this->id_recolte;
    }

    public function setId_recolte($value)
    {
        $this->id_recolte = $value;
    }
}
