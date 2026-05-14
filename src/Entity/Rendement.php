<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use App\Entity\Recolte;


#[ORM\Entity]
class Rendement
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private int $id_rendement;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'La surface exploitée est obligatoire.')]
    #[Assert\Positive(message: 'La surface exploitée doit être strictement supérieure à 0.')]
    private string $surface_exploitee;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    #[Assert\NotNull(message: 'La quantité totale est obligatoire.')]
    #[Assert\Positive(message: 'La quantité totale doit être strictement supérieure à 0.')]
    private string $quantite_totale;

    #[ORM\Column(type: "decimal", precision: 10, scale: 4)]
    private string $productivite;

    #[ORM\ManyToOne(targetEntity: Recolte::class)]
    #[ORM\JoinColumn(name: 'id_recolte', referencedColumnName: 'id_recolte')]
    #[Assert\NotNull(message: 'La récolte est obligatoire.')]
    private ?Recolte $id_recolte = null;

    #[Assert\Callback]
    public function validateBusinessRules(ExecutionContextInterface $context): void
    {
        if ($this->id_recolte === null) {
            return;
        }

        // Règle métier: quantité totale saisie ne doit pas dépasser la quantité de la récolte.
        // (Protection si l'utilisateur saisit une valeur incohérente)
        $recolteQuantite = $this->id_recolte->getQuantite();
        $quantiteTotale = (float) $this->quantite_totale;
        
        if (is_numeric($recolteQuantite) && $quantiteTotale > (float) $recolteQuantite) {
            $context->buildViolation('La quantité totale ne peut pas dépasser la quantité de la récolte sélectionnée ({{ q }} kg).')
                ->setParameter('{{ q }}', (string) $recolteQuantite)
                ->atPath('quantite_totale')
                ->addViolation();
        }
    }

    public function getId_rendement()
    {
        return $this->id_rendement;
    }

    public function setId_rendement($value)
    {
        $this->id_rendement = $value;
    }

    public function getSurface_exploitee(): string
    {
        return $this->surface_exploitee;
    }

    public function setSurface_exploitee(string $value): self
    {
        $this->surface_exploitee = $value;
        return $this;
    }

    public function getQuantite_totale(): string
    {
        return $this->quantite_totale;
    }

    public function setQuantite_totale(string $value): self
    {
        $this->quantite_totale = $value;
        return $this;
    }

    public function getProductivite(): string
    {
        return $this->productivite;
    }

    public function setProductivite(string $value): self
    {
        $this->productivite = $value;
        return $this;
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
        $this->setId_recolte($value);

        return $this;
    }

    public function getSurfaceExploitee()
    {
        return $this->getSurface_exploitee();
    }

    public function setSurfaceExploitee($value)
    {
        $this->setSurface_exploitee($value);

        return $this;
    }

    public function getQuantiteTotale()
    {
        return $this->getQuantite_totale();
    }

    public function setQuantiteTotale($value)
    {
        $this->setQuantite_totale($value);

        return $this;
    }

    public function getIdRendement()
    {
        return $this->getId_rendement();
    }

    public function setIdRendement($value)
    {
        $this->setId_rendement($value);

        return $this;
    }
}
