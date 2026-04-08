<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use App\Entity\Parcelle;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Entity\Recolte;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
#[Assert\Callback('validateDates')]
class Culture
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id_culture = null;

    #[ORM\Column(type: "string", length: 100)]
    #[Assert\NotBlank(message: 'Le type de culture est obligatoire.')]
    private ?string $type_culture = null;

    #[ORM\Column(type: "date")]
    #[Assert\NotNull(message: 'La date de plantation est obligatoire.')]
    private ?\DateTimeInterface $date_plantation = null;

    #[ORM\Column(type: "date")]
    #[Assert\NotNull(message: 'La date de récolte prévue est obligatoire.')]
    private ?\DateTimeInterface $date_recolte_prevue = null;

    #[ORM\Column(type: "string", length: 50)]
    #[Assert\NotBlank(message: 'L\'état de croissance est obligatoire.')]
    #[Assert\Choice(choices: [
        'germination',
        'croissance',
        'floraison',
        'maturite',
    ], message: 'État de croissance invalide.')]
    private ?string $etat_croissance = null;

    #[ORM\ManyToOne(targetEntity: Parcelle::class, inversedBy: "cultures")]
    #[ORM\JoinColumn(name: 'id_parcelle', referencedColumnName: 'id_parcelle', nullable: false)]
    #[Assert\NotNull(message: 'Vous devez choisir une parcelle.')]
    private ?Parcelle $id_parcelle = null;

    #[ORM\OneToMany(mappedBy: "id_culture", targetEntity: Recolte::class)]
    private Collection $recoltes;

    public function __construct()
    {
        $this->recoltes = new ArrayCollection();
    }

    public function getId_culture(): ?int
    {
        return $this->id_culture;
    }

    public function getType_culture(): ?string
    {
        return $this->type_culture;
    }

    public function setType_culture(?string $value): self
    {
        $this->type_culture = $value;

        return $this;
    }

    public function getTypeCulture(): ?string
    {
        return $this->getType_culture();
    }

    public function setTypeCulture(?string $value): self
    {
        return $this->setType_culture($value);
    }

    public function getDate_plantation(): ?\DateTimeInterface
    {
        return $this->date_plantation;
    }

    public function setDate_plantation(?\DateTimeInterface $value): self
    {
        $this->date_plantation = $value;

        return $this;
    }

    public function getDatePlantation(): ?\DateTimeInterface
    {
        return $this->getDate_plantation();
    }

    public function setDatePlantation(?\DateTimeInterface $value): self
    {
        return $this->setDate_plantation($value);
    }

    public function getDate_recolte_prevue(): ?\DateTimeInterface
    {
        return $this->date_recolte_prevue;
    }

    public function setDate_recolte_prevue(?\DateTimeInterface $value): self
    {
        $this->date_recolte_prevue = $value;

        return $this;
    }

    public function getDateRecoltePrevue(): ?\DateTimeInterface
    {
        return $this->getDate_recolte_prevue();
    }

    public function setDateRecoltePrevue(?\DateTimeInterface $value): self
    {
        return $this->setDate_recolte_prevue($value);
    }

    public function getEtat_croissance(): ?string
    {
        return $this->etat_croissance;
    }

    public function setEtat_croissance(?string $value): self
    {
        $this->etat_croissance = $value;

        return $this;
    }

    public function getEtatCroissance(): ?string
    {
        return $this->getEtat_croissance();
    }

    public function setEtatCroissance(?string $value): self
    {
        return $this->setEtat_croissance($value);
    }

    public function getId_parcelle(): ?Parcelle
    {
        return $this->id_parcelle;
    }

    public function setId_parcelle(?Parcelle $value): self
    {
        $this->id_parcelle = $value;

        return $this;
    }

    // Aliases with conventional naming for Symfony Forms/Twig
    public function getParcelle(): ?Parcelle
    {
        return $this->getId_parcelle();
    }

    public function setParcelle(?Parcelle $parcelle): self
    {
        return $this->setId_parcelle($parcelle);
    }

        /** @return Collection<int, Recolte> */
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

        public function validateDates(ExecutionContextInterface $context): void
        {
            if (!isset($this->date_plantation, $this->date_recolte_prevue) || $this->date_plantation === null || $this->date_recolte_prevue === null) {
                return;
            }

            if ($this->date_recolte_prevue <= $this->date_plantation) {
                $context
                    ->buildViolation('La date de récolte prévue doit être supérieure à la date de plantation.')
                    ->atPath('date_recolte_prevue')
                    ->addViolation();
            }
        }
}
