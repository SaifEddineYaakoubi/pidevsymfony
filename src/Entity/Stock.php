<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Produit;

#[ORM\Entity]
class Stock
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_stock;

    #[ORM\Column(type: "float")]
    private float $quantite;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_entree;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_expiration;

        #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: "stocks")]
    #[ORM\JoinColumn(name: 'id_produit', referencedColumnName: 'id_produit', onDelete: 'CASCADE')]
    private Produit $id_produit;

    #[ORM\Column(type: "integer")]
    private int $id_user;

    public function getId_stock()
    {
        return $this->id_stock;
    }

    public function setId_stock($value)
    {
        $this->id_stock = $value;
    }

    public function getQuantite()
    {
        return $this->quantite;
    }

    public function setQuantite($value)
    {
        $this->quantite = $value;
    }

    public function getDate_entree()
    {
        return $this->date_entree;
    }

    public function setDate_entree($value)
    {
        $this->date_entree = $value;
    }

    public function getDate_expiration()
    {
        return $this->date_expiration;
    }

    public function setDate_expiration($value)
    {
        $this->date_expiration = $value;
    }

    public function getId_produit()
    {
        return $this->id_produit;
    }

    public function setId_produit($value)
    {
        $this->id_produit = $value;
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
