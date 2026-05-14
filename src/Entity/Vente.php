<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\VenteRepository;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: VenteRepository::class)]
#[ORM\HasLifecycleCallbacks] // <--- MOHIM: bech el calcul automatique i-khdem
class Vente
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id_vente = null;

    #[ORM\Column(type: "date")]
    #[Assert\Range(
        min: "1970-01-01",
        max: "+1 day",
        notInRangeMessage: "La date de vente est invalide."
    )]
    private \DateTimeInterface $date_vente;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    #[Assert\Positive(message: "Le montant doit être un nombre positif.")]
    private string $montant_total;

    /**
     * Flag pour indiquer si le montant a été calculé manuellement avec réduction
     * Si true, le PrePersist ne recalculera pas le montant
     */
    private bool $montantCalculatedWithDiscount = false;

    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    #[Assert\NotNull(message: "La quantité vendue est obligatoire.")]
    #[Assert\Positive(message: "La quantité doit être un nombre positif.")]
    private string $quantite;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'ventes')]
    #[ORM\JoinColumn(name: 'id_client', referencedColumnName: 'id_client', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "Le client est obligatoire.")]
    private ?Client $id_client = null;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class, inversedBy: 'ventes')]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', onDelete: 'CASCADE')]
    private ?Utilisateur $id_user = null;

    #[ORM\ManyToOne(targetEntity: Produit::class)]
    #[ORM\JoinColumn(name: 'id_produit', referencedColumnName: 'id_produit', onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "Le produit est obligatoire.")]
    private ?Produit $id_produit = null;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(type: "string", length: 100, nullable: true)]
    private ?string $region = null;

    #[ORM\Column(type: "float", nullable: true)]
    private ?float $frais_livraison = null;

    // ======================================================
    // LOGIC AUTOMATIQUE: Calcul du montant avant sauvegarde
    // ======================================================
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function calculateMontant(): void
    {
        // Si le montant a déjà été calculé avec réduction, ne pas le recalculer
        if ($this->montantCalculatedWithDiscount) {
            return;
        }

        if ($this->id_produit !== null && $this->quantite !== null) {
            // Montant = Prix du produit * Quantité (sans réduction)
            $prixUnitaire = (float) $this->id_produit->getPrixUnitaire();
            $quantite = (float) $this->quantite;
            $this->montant_total = (string) ($prixUnitaire * $quantite);
        }
    }

    /**
     * Définit le montant total avec réduction déjà appliquée
     * Empêche le recalcul automatique
     */
    public function setMontantTotalWithDiscount(float $montant_total): self
    {
        $this->montant_total = (string) $montant_total;
        $this->montantCalculatedWithDiscount = true;
        return $this;
    }

    // ================= Getters & Setters =================

    public function getIdVente(): ?int { return $this->id_vente; }

    public function getDateVente(): \DateTimeInterface { return $this->date_vente; }
    public function setDateVente(\DateTimeInterface $date_vente): self { $this->date_vente = $date_vente; return $this; }

    public function getMontantTotal(): string { return $this->montant_total; }
    public function setMontantTotal(string $montant_total): self { $this->montant_total = $montant_total; return $this; }

    public function getQuantite(): string { return $this->quantite; }
    public function setQuantite(string $quantite): self { $this->quantite = $quantite; return $this; }

    public function getIdClient(): ?Client { return $this->id_client; }
    public function setIdClient(?Client $client): self { $this->id_client = $client; return $this; }

    public function getIdUser(): ?Utilisateur { return $this->id_user; }
    public function setIdUser(?Utilisateur $user): self { $this->id_user = $user; return $this; }

    public function getIdProduit(): ?Produit { return $this->id_produit; }
    public function setIdProduit(?Produit $produit): self { $this->id_produit = $produit; return $this; }

    public function getVille(): ?string { return $this->ville; }
    public function setVille(?string $ville): self { $this->ville = $ville; return $this; }

    public function getRegion(): ?string { return $this->region; }
    public function setRegion(?string $region): self { $this->region = $region; return $this; }

    public function getFraisLivraison(): ?float { return $this->frais_livraison; }
    public function setFraisLivraison(?float $frais_livraison): self { $this->frais_livraison = $frais_livraison; return $this; }
}