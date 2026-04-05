<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Parcelle;
use Doctrine\Common\Collections\Collection;
use App\Entity\Recolte;

#[ORM\Entity]
class Culture
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id_culture;

    #[ORM\Column(type: "string", length: 100)]
    private string $type_culture;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_plantation;

    #[ORM\Column(type: "date")]
    private \DateTimeInterface $date_recolte_prevue;

    #[ORM\Column(type: "string", length: 50)]
    private string $etat_croissance;

        #[ORM\ManyToOne(targetEntity: Parcelle::class, inversedBy: "cultures")]
    #[ORM\JoinColumn(name: 'id_parcelle', referencedColumnName: 'id_parcelle', onDelete: 'CASCADE')]
    private Parcelle $id_parcelle;

    public function getId_culture()
    {
        return $this->id_culture;
    }

    public function setId_culture($value)
    {
        $this->id_culture = $value;
    }

    public function getType_culture()
    {
        return $this->type_culture;
    }

    public function setType_culture($value)
    {
        $this->type_culture = $value;
    }

    public function getDate_plantation()
    {
        return $this->date_plantation;
    }

    public function setDate_plantation($value)
    {
        $this->date_plantation = $value;
    }

    public function getDate_recolte_prevue()
    {
        return $this->date_recolte_prevue;
    }

    public function setDate_recolte_prevue($value)
    {
        $this->date_recolte_prevue = $value;
    }

    public function getEtat_croissance()
    {
        return $this->etat_croissance;
    }

    public function setEtat_croissance($value)
    {
        $this->etat_croissance = $value;
    }

    public function getId_parcelle()
    {
        return $this->id_parcelle;
    }

    public function setId_parcelle($value)
    {
        $this->id_parcelle = $value;
    }

    #[ORM\OneToMany(mappedBy: "id_culture", targetEntity: Recolte::class)]
    private Collection $recoltes;

        public function getRecoltes(): Collection
        {
            return $this->recoltes;
        }
    
        public function addRecolte(Recolte $recolte): self
        {
            if (!$this->recoltes->contains($recolte)) {
                $this->recoltes[] = $recolte;
                $recolte->setId_culture($this);
            }
    
            return $this;
        }
    
        public function removeRecolte(Recolte $recolte): self
        {
            if ($this->recoltes->removeElement($recolte)) {
                // set the owning side to null (unless already changed)
                if ($recolte->getId_culture() === $this) {
                    $recolte->setId_culture(null);
                }
            }
    
            return $this;
        }
}
