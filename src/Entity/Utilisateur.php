<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Doctrine\Common\Collections\Collection;
use App\Entity\Vente;

#[ORM\Entity]
class Utilisateur
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_user;

    #[ORM\Column(type: "string", length: 100)]
    private string $nom;

    #[ORM\Column(type: "string", length: 100)]
    private string $prenom;

    #[ORM\Column(type: "string", length: 150)]
    private string $email;

    #[ORM\Column(type: "string", length: 255)]
    private string $mot_de_passe;

    #[ORM\Column(type: "string", length: 50)]
    private string $role;

    #[ORM\Column(type: "boolean")]
    private bool $statut;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_creation;

    #[ORM\Column(type: "string", length: 255)]
    private string $face_image_path;

    #[ORM\Column(type: "integer")]
    private int $id_agriculteur;

    public function getId_user()
    {
        return $this->id_user;
    }

    public function setId_user($value)
    {
        $this->id_user = $value;
    }

    public function getNom()
    {
        return $this->nom;
    }

    public function setNom($value)
    {
        $this->nom = $value;
    }

    public function getPrenom()
    {
        return $this->prenom;
    }

    public function setPrenom($value)
    {
        $this->prenom = $value;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($value)
    {
        $this->email = $value;
    }

    public function getMot_de_passe()
    {
        return $this->mot_de_passe;
    }

    public function setMot_de_passe($value)
    {
        $this->mot_de_passe = $value;
    }

    public function getRole()
    {
        return $this->role;
    }

    public function setRole($value)
    {
        $this->role = $value;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function setStatut($value)
    {
        $this->statut = $value;
    }

    public function getDate_creation()
    {
        return $this->date_creation;
    }

    public function setDate_creation($value)
    {
        $this->date_creation = $value;
    }

    public function getFace_image_path()
    {
        return $this->face_image_path;
    }

    public function setFace_image_path($value)
    {
        $this->face_image_path = $value;
    }

    public function getId_agriculteur()
    {
        return $this->id_agriculteur;
    }

    public function setId_agriculteur($value)
    {
        $this->id_agriculteur = $value;
    }

    #[ORM\OneToMany(mappedBy: "id_user", targetEntity: Parcelle::class)]
    private Collection $parcelles;

        public function getParcelles(): Collection
        {
            return $this->parcelles;
        }
    
        public function addParcelle(Parcelle $parcelle): self
        {
            if (!$this->parcelles->contains($parcelle)) {
                $this->parcelles[] = $parcelle;
                $parcelle->setId_user($this);
            }
    
            return $this;
        }
    
        public function removeParcelle(Parcelle $parcelle): self
        {
            if ($this->parcelles->removeElement($parcelle)) {
                // set the owning side to null (unless already changed)
                if ($parcelle->getId_user() === $this) {
                    $parcelle->setId_user(null);
                }
            }
    
            return $this;
        }

    #[ORM\OneToMany(mappedBy: "id_user", targetEntity: Vente::class)]
    private Collection $ventes;
}
