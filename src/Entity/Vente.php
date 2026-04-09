<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\VenteRepository;
use Symfony\Component\Validator\Constraints as Assert;

use App\Entity\Utilisateur;

#[ORM\Entity(repositoryClass: VenteRepository::class)]
class Vente
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_vente;

    #[ORM\Column(type: "date", nullable: true)]
    #[Assert\NotNull(message: "La date de vente est obligatoire.")]
    #[Assert\Type(type: "\DateTimeInterface", message: "La date de vente doit être une date valide.")]
    #[Assert\LessThanOrEqual(
        value: "today",
        message: "La date de vente ne peut pas être dans le futur."
    )]
    private ?\DateTimeInterface $date_vente = null;

    #[ORM\Column(type: "float", nullable: true)]
    #[Assert\NotNull(message: "Le montant total est obligatoire.")]
    #[Assert\Type(type: "float", message: "Le montant doit être un nombre valide.")]
    #[Assert\Positive(message: "Le montant doit être un nombre positif.")]
    #[Assert\LessThan(
        value: 1000000,
        message: "Le montant ne peut pas dépasser 1 000 000."
    )]
    private ?float $montant_total = null;


        #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: "ventes")]
    #[ORM\JoinColumn(name: 'id_client', referencedColumnName: 'id_client', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "Le client est obligatoire.")]
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
