<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Vente;

#[ORM\Entity]
class Client
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_client;

    #[ORM\Column(type: "string", length: 100)]
    private string $nom;

    #[ORM\Column(type: "string", length: 100)]
    private string $contact;

    #[ORM\Column(type: "string", length: 150)]
    private string $adresse;

    #[ORM\Column(type: "integer")]
    private int $id_user;

    public function getId_client()
    {
        return $this->id_client;
    }

    public function setId_client($value)
    {
        $this->id_client = $value;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($value)
    {
        $this->nom = $value;
    }

    public function getContact()
    {
        return $this->contact;
    }

    public function setContact($value)
    {
        $this->contact = $value;
    }

    public function getAdresse()
    {
        return $this->adresse;
    }

    public function setAdresse($value)
    {
        $this->adresse = $value;
    }

    public function getId_user()
    {
        return $this->id_user;
    }

    public function setId_user($value)
    {
        $this->id_user = $value;
    }

    #[ORM\OneToMany(mappedBy: "id_client", targetEntity: Vente::class)]
    private Collection $ventes;

        public function getVentes(): Collection
        {
            return $this->ventes;
        }
    
        public function addVente(Vente $vente): self
        {
            if (!$this->ventes->contains($vente)) {
                $this->ventes[] = $vente;
                $vente->setId_client($this);
            }
    
            return $this;
        }
    
        public function removeVente(Vente $vente): self
        {
            if ($this->ventes->removeElement($vente)) {
                // set the owning side to null (unless already changed)
                if ($vente->getId_client() === $this) {
                    $vente->setId_client(null);
                }
            }
    
            return $this;
        }
}
