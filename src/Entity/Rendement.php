<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Recolte;


#[ORM\Entity]
class Rendement
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_rendement;

    #[ORM\Column(type: "float")]
    private float $surface_exploitee;

    #[ORM\Column(type: "float")]
    private float $quantite_totale;

    #[ORM\Column(type: "float")]
    private float $productivite;

    #[ORM\ManyToOne(targetEntity: Recolte::class)]
    #[ORM\JoinColumn(name: 'id_recolte', referencedColumnName: 'id_recolte')]
    private ?Recolte $id_recolte = null;

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

    public function getId_recolte(): ?Recolte
    {
        return $this->id_recolte;
    }

    public function setId_recolte(?Recolte $value): void
    {
        $this->id_recolte = $value;
    }

    public function getIdRecolte()
    {
        return $this->getId_recolte();
    }

    public function setIdRecolte($value)
    {
        return $this->setId_recolte($value);
    }

    public function getSurfaceExploitee()
    {
        return $this->getSurface_exploitee();
    }

    public function setSurfaceExploitee($value)
    {
        return $this->setSurface_exploitee($value);
    }

    public function getQuantiteTotale()
    {
        return $this->getQuantite_totale();
    }

    public function setQuantiteTotale($value)
    {
        return $this->setQuantite_totale($value);
    }

    public function getIdRendement()
    {
        return $this->getId_rendement();
    }

    public function setIdRendement($value)
    {
        return $this->setId_rendement($value);
    }
}
