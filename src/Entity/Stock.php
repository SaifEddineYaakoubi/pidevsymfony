<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_stock = null;

    #[ORM\Column(type: 'float')]
    private ?float $quantite = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $date_entree = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $date_expiration = null;

    #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'stocks')]
    #[ORM\JoinColumn(name: 'id_produit', referencedColumnName: 'id_produit', onDelete: 'CASCADE')]
    private ?Produit $id_produit = null;

    #[ORM\Column(type: 'integer')]
    private ?int $id_user = null;

    public function getId_stock(): ?int
    {
        return $this->id_stock;
    }

    public function getIdStock(): ?int
    {
        return $this->id_stock;
    }

    public function getQuantite(): ?float
    {
        return $this->quantite;
    }

    public function setQuantite(?float $quantite): self
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getDateEntree(): ?\DateTimeInterface
    {
        return $this->date_entree;
    }

    public function setDateEntree(?\DateTimeInterface $dateEntree): self
    {
        $this->date_entree = $dateEntree;

        return $this;
    }

    public function getDateExpiration(): ?\DateTimeInterface
    {
        return $this->date_expiration;
    }

    public function setDateExpiration(?\DateTimeInterface $dateExpiration): self
    {
        $this->date_expiration = $dateExpiration;

        return $this;
    }

    public function getIdProduit(): ?Produit
    {
        return $this->id_produit;
    }

    public function setIdProduit(?Produit $idProduit): self
    {
        $this->id_produit = $idProduit;

        return $this;
    }

    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function setIdUser(?int $idUser): self
    {
        $this->id_user = $idUser;

        return $this;
    }
}
