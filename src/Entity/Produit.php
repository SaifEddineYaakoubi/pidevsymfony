<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Stock;

#[ORM\Entity]
class Produit
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_produit;

    #[ORM\Column(type: "string", length: 100)]
    private string $nom;

    #[ORM\Column(type: "string", length: 50)]
    private string $type;

    #[ORM\Column(type: "string", length: 20)]
    private string $unite;

    #[ORM\Column(type: "float")]
    private float $prix_unitaire;

    #[ORM\Column(type: "integer")]
    private int $id_user;

    public function getId_produit()
    {
        return $this->id_produit;
    }

    public function setId_produit($value)
    {
        $this->id_produit = $value;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($value)
    {
        $this->nom = $value;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($value)
    {
        $this->type = $value;
    }

    public function getUnite()
    {
        return $this->unite;
    }

    public function setUnite($value)
    {
        $this->unite = $value;
    }

    public function getPrix_unitaire()
    {
        return $this->prix_unitaire;
    }

    public function setPrix_unitaire($value)
    {
        $this->prix_unitaire = $value;
    }

    public function getId_user()
    {
        return $this->id_user;
    }

    public function setId_user($value)
    {
        $this->id_user = $value;
    }

    #[ORM\OneToMany(mappedBy: "id_produit", targetEntity: Stock::class)]
    private Collection $stocks;

        public function getStocks(): Collection
        {
            return $this->stocks;
        }
    
        public function addStock(Stock $stock): self
        {
            if (!$this->stocks->contains($stock)) {
                $this->stocks[] = $stock;
                $stock->setId_produit($this);
            }
    
            return $this;
        }
    
        public function removeStock(Stock $stock): self
        {
            if ($this->stocks->removeElement($stock)) {
                // set the owning side to null (unless already changed)
                if ($stock->getId_produit() === $this) {
                    $stock->setId_produit(null);
                }
            }
    
            return $this;
        }
}
