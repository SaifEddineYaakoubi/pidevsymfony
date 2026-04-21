<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_produit = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le nom du produit est obligatoire.')]
    #[Assert\Length(
        max: 100,
        maxMessage: 'Le nom ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $nom = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Assert\NotBlank(message: 'Le type est obligatoire.')]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Le type ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $type = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank(message: 'L\'unité est obligatoire.')]
    #[Assert\Length(
        max: 20,
        maxMessage: 'L\'unité ne doit pas dépasser {{ limit }} caractères.'
    )]
    private ?string $unite = null;

    #[ORM\Column(type: 'float')]
    #[Assert\NotNull(message: 'Le prix unitaire est obligatoire.')]
    #[Assert\PositiveOrZero(message: 'Le prix unitaire doit être supérieur ou égal à 0.')]
    private ?float $prix_unitaire = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private ?bool $alertEnvoyee = false;

    #[ORM\ManyToOne(targetEntity: Utilisateur::class)]
    #[ORM\JoinColumn(name: 'id_user', referencedColumnName: 'id_user', nullable: true)]
    private ?Utilisateur $utilisateur = null;

    #[ORM\OneToMany(mappedBy: 'id_produit', targetEntity: Stock::class, cascade: ['persist', 'remove'])]
    private Collection $stocks;

    public function __construct()
    {
        $this->stocks = new ArrayCollection();
    }

    public function getId_produit(): ?int
    {
        return $this->id_produit;
    }

    public function getIdProduit(): ?int
    {
        return $this->id_produit;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getUnite(): ?string
    {
        return $this->unite;
    }

    public function setUnite(?string $unite): self
    {
        $this->unite = $unite;

        return $this;
    }

    public function getPrixUnitaire(): ?float
    {
        return $this->prix_unitaire;
    }

    public function setPrixUnitaire(?float $prixUnitaire): self
    {
        $this->prix_unitaire = $prixUnitaire;

        return $this;
    }

    public function getIdUser(): ?int
    {
        return $this->utilisateur?->getIdUser();
    }

    public function setIdUser(?int $idUser): self
    {
        if ($idUser === null) {
            $this->utilisateur = null;
        } else {
            // Cette méthode est gardée pour compatibilité, mais il est préférable d'utiliser setUtilisateur()
            // Pour l'instant, on ne fait rien car Doctrine gérera la relation
        }

        return $this;
    }

    public function getUtilisateur(): ?Utilisateur
    {
        return $this->utilisateur;
    }

    public function setUtilisateur(?Utilisateur $utilisateur): self
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    public function getStocks(): Collection
    {
        return $this->stocks;
    }

    public function getQuantite(): float
    {
        $total = 0.0;

        foreach ($this->getStocks() as $stock) {
            $total += $stock->getQuantite() ?? 0.0;
        }

        return $total;
    }

    public function addStock(Stock $stock): self
    {
        if (!$this->stocks->contains($stock)) {
            $this->stocks[] = $stock;
            $stock->setIdProduit($this);
        }

        return $this;
    }

    public function removeStock(Stock $stock): self
    {
        if ($this->stocks->removeElement($stock)) {
            if ($stock->getIdProduit() === $this) {
                $stock->setIdProduit(null);
            }
        }

        return $this;
    }

    /**
     * Retourne une icône/emoji basée sur le nom du produit
     */
    public function getIcon(): string
    {
        $nom = strtolower($this->nom ?? '');
        
        // Mapping des produits à leurs icônes
        $iconMap = [
            'pomme de terre' => '🥔',
            'tomate' => '🍅',
            'carotte' => '🥕',
            'salade' => '🥗',
            'oignon' => '🧅',
            'ail' => '🧄',
            'poivron' => '🫑',
            'concombre' => '🥒',
            'courgette' => '🥒',
            'brocoli' => '🥦',
            'épinard' => '🥬',
            'laitue' => '🥬',
            'chou' => '🥬',
            'haricot' => '🫛',
            'pois' => '🫛',
            'betterave' => '🍠',
            'navet' => '🥔',
            'champignon' => '🍄',
            'maïs' => '🌽',
            'courge' => '🎃',
            'pastèque' => '🍉',
            'melon' => '🍈',
            'fraise' => '🍓',
            'cerise' => '🍒',
            'raisin' => '🍇',
            'pomme' => '🍎',
            'poire' => '🍐',
            'pêche' => '🍑',
            'abricot' => '🍑',
            'prune' => '🍒',
            'banane' => '🍌',
            'kiwi' => '🥝',
            'noix' => '🥜',
            'amande' => '🥜',
            'cacahuète' => '🥜',
            'miel' => '🍯',
            'oeufs' => '🥚',
            'fromage' => '🧀',
            'lait' => '🥛',
            'beurre' => '🧈',
            'pain' => '🍞',
            'riz' => '🍚',
            'blé' => '🌾',
            'poulet' => '🍗',
            'viande' => '🥩',
            'poisson' => '🐟',
            'crevette' => '🍤',
            'huile' => '🫒',
            'café' => '☕',
            'thé' => '🫖',
            'jus' => '🧃',
            'eau' => '💧',
        ];

        // Vérification exacte d'abord
        if (isset($iconMap[$nom])) {
            return $iconMap[$nom];
        }

        // Recherche par contenant (partial match)
        foreach ($iconMap as $key => $icon) {
            if (strpos($nom, $key) !== false || strpos($key, $nom) !== false) {
                return $icon;
            }
        }

        // Icône par défaut
        return '🥬';
    }

    public function isAlertEnvoyee(): ?bool
    {
        return $this->alertEnvoyee;
    }

    public function setAlertEnvoyee(?bool $alertEnvoyee): self
    {
        $this->alertEnvoyee = $alertEnvoyee;

        return $this;
    }
}
