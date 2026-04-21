<?php

namespace App\Entity;

use App\Entity\Utilisateur;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity]
class Stock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_stock = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull(message: 'La quantité est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'La quantité doit être supérieure ou égale à 0.')]
    private ?float $quantite = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull(message: 'La date d\'entrée est obligatoire.')]
    #[Assert\Type(type: \DateTimeInterface::class, message: 'La date d\'entrée n\'est pas valide.')]
    private ?\DateTimeInterface $date_entree = null;

    #[ORM\Column(type: 'date')]
    #[Assert\NotNull(message: 'La date d\'expiration est obligatoire.')]
    #[Assert\Type(type: \DateTimeInterface::class, message: 'La date d\'expiration n\'est pas valide.')]
    private ?\DateTimeInterface $date_expiration = null;

    #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'stocks')]
    #[ORM\JoinColumn(name: 'id_produit', referencedColumnName: 'id_produit', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'Le produit est obligatoire.')]
    private ?Produit $id_produit = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', nullable: true)]
    private ?Utilisateur $id_user = null;

    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        if ($this->date_entree === null || $this->date_expiration === null) {
            return;
        }

        if ($this->date_expiration < $this->date_entree) {
            $context->buildViolation('La date d\'expiration doit être supérieure ou égale à la date d\'entrée.')
                ->atPath('date_expiration')
                ->addViolation();
        }
    }

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
        return $this->id_user?->getIdUser();
    }

    public function setIdUser(?int $idUser): self
    {
        // Cette méthode est conservée pour compatibilité.
        // Pour remplir la relation, utilisez setUtilisateur() lorsque vous avez une instance Utilisateur.
        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->id_user;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->id_user = $utilisateur;

        return $this;
    }
}
