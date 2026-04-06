<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Utilisateur;

#[ORM\Entity]
class Vente
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_vente;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_vente;

    #[ORM\Column(type: "float")]
    private float $montant_total;


        #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: "ventes")]
    #[ORM\JoinColumn(name: 'id_client', referencedColumnName: 'id_client', onDelete: 'CASCADE')]
    private ?Client $id_client = null;

        #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: "ventes")]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', onDelete: 'CASCADE')]
    private ?Utilisateur $id_user = null;

    public function getId_vente()
    {
        return $this->id_vente;
    }

    public function setId_vente($value)
    {
        $this->id_vente = $value;
    }

    public function getDate_vente()
    {
        return $this->date_vente;
    }

    public function setDate_vente($value)
    {
        $this->date_vente = $value;
    }

    // Getters/Setters camelCase pour Symfony
    public function getDateVente()
    {
        return $this->date_vente;
    }

    public function setDateVente($value)
    {
        $this->date_vente = $value;
        return $this;
    }

    public function getMontantTotal()
    {
        return $this->montant_total;
    }

    public function setMontantTotal($value)
    {
        $this->montant_total = $value;
        return $this;
    }

    public function getMontant_total()
    {
        return $this->montant_total;
    }

    public function setMontant_total($value)
    {
        $this->montant_total = $value;
    }

    public function getId_client()
    {
        return $this->id_client;
    }

    public function setId_client($value)
    {
        $this->id_client = $value;
    }

    // Getters/Setters camelCase pour les relations
    public function getIdClient()
    {
        return $this->id_client;
    }

    public function setIdClient($value)
    {
        $this->id_client = $value;
        return $this;
    }

    public function getId_user()
    {
        return $this->id_user;
    }

    public function setId_user($value)
    {
        $this->id_user = $value;
    }

    public function getIdUser()
    {
        return $this->id_user;
    }

    public function setIdUser($value)
    {
        $this->id_user = $value;
        return $this;
    }
}
