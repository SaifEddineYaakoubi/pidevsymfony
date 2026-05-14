<?php

namespace App\Service;

use App\Entity\Stock;
use App\Entity\Produit;
use App\Entity\Utilisateur;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Service métier pour la gestion des stocks
 * 
 * Responsabilités:
 * - Valider les règles métier spécifiques aux stocks
 * - Gérer la création et modification de stocks
 * - Analyser l'expiration des produits
 * - Calculer les statistiques de stock
 */
class StockManager
{
    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    /**
     * Valide un stock selon les règles métier
     * 
     * @param Stock $stock Le stock à valider
     * @return ConstraintViolationListInterface Les violations trouvées
     */
    public function validate(Stock $stock): ConstraintViolationListInterface
    {
        return $this->validator->validate($stock);
    }

    /**
     * Crée un nouveau stock avec validation
     * 
     * @param string $quantite La quantité
     * @param \DateTimeInterface $dateEntree La date d'entrée
     * @param \DateTimeInterface $dateExpiration La date d'expiration
     * @param Produit $produit Le produit
     * @param Utilisateur|null $utilisateur L'utilisateur (optionnel)
     * 
     * @return Stock Le stock créé
     * @throws \InvalidArgumentException Si les données sont invalides
     */
    public function createStock(
        string $quantite,
        \DateTimeInterface $dateEntree,
        \DateTimeInterface $dateExpiration,
        Produit $produit,
        ?Utilisateur $utilisateur = null
    ): Stock {
        // Valider que la quantité est positive ou zéro
        if ((float) $quantite < 0) {
            throw new \InvalidArgumentException(
                'La quantité doit être supérieure ou égale à 0.'
            );
        }

        // Valider que la date d'expiration est >= date d'entrée
        if ($dateExpiration < $dateEntree) {
            throw new \InvalidArgumentException(
                'La date d\'expiration doit être supérieure ou égale à la date d\'entrée.'
            );
        }

        $stock = new Stock();
        $stock->setQuantite($quantite);
        $stock->setDateEntree($dateEntree);
        $stock->setDateExpiration($dateExpiration);
        $stock->setIdProduit($produit);
        
        if ($utilisateur !== null) {
            $stock->setUtilisateur($utilisateur);
        }

        // Valider le stock créé
        $violations = $this->validate($stock);
        if ($violations->count() > 0) {
            throw new \InvalidArgumentException(
                'Le stock créé ne respecte pas les contraintes de validation: ' . 
                (string) $violations[0]->getMessage()
            );
        }

        return $stock;
    }

    /**
     * Valide que la quantité est positive ou zéro
     * 
     * @param string $quantite La quantité
     * @return bool True si la quantité est valide
     * @throws \InvalidArgumentException Si la quantité n'est pas valide
     */
    public function validateQuantite(string $quantite): bool
    {
        if ((float) $quantite < 0) {
            throw new \InvalidArgumentException(
                'La quantité doit être supérieure ou égale à 0.'
            );
        }

        return true;
    }

    /**
     * Valide que les dates sont cohérentes
     * 
     * @param \DateTimeInterface $dateEntree La date d'entrée
     * @param \DateTimeInterface $dateExpiration La date d'expiration
     * @return bool True si les dates sont valides
     * @throws \InvalidArgumentException Si les dates ne sont pas valides
     */
    public function validateDates(
        \DateTimeInterface $dateEntree,
        \DateTimeInterface $dateExpiration
    ): bool {
        if ($dateExpiration < $dateEntree) {
            throw new \InvalidArgumentException(
                'La date d\'expiration doit être supérieure ou égale à la date d\'entrée.'
            );
        }

        return true;
    }

    /**
     * Vérifie si un stock est expiré
     * 
     * @param Stock $stock Le stock
     * @return bool True si le stock est expiré
     */
    public function isExpired(Stock $stock): bool
    {
        $now = new \DateTime();
        return $stock->getDateExpiration() < $now;
    }

    /**
     * Vérifie si un stock expire bientôt (dans 30 jours)
     * 
     * @param Stock $stock Le stock
     * @return bool True si le stock expire bientôt
     */
    public function isExpiringsoon(Stock $stock): bool
    {
        $now = new \DateTime();
        $thirtyDaysLater = (clone $now)->modify('+30 days');
        
        return $stock->getDateExpiration() <= $thirtyDaysLater &&
               $stock->getDateExpiration() > $now;
    }

    /**
     * Obtient le nombre de jours avant expiration
     * 
     * @param Stock $stock Le stock
     * @return int Le nombre de jours (négatif si expiré)
     */
    public function getDaysBeforeExpiration(Stock $stock): int
    {
        $now = new \DateTime();
        $diff = $stock->getDateExpiration()->diff($now);
        
        if ($stock->getDateExpiration() < $now) {
            return -$diff->days;
        }
        
        return $diff->days;
    }

    /**
     * Augmente la quantité du stock
     * 
     * @param Stock $stock Le stock
     * @param string $quantite La quantité à ajouter
     * @return Stock Le stock mis à jour
     */
    public function increaseQuantite(Stock $stock, string $quantite): Stock
    {
        $currentQuantite = (float) $stock->getQuantite();
        $addQuantite = (float) $quantite;
        $newQuantite = $currentQuantite + $addQuantite;
        
        $stock->setQuantite((string) $newQuantite);
        return $stock;
    }

    /**
     * Diminue la quantité du stock
     * 
     * @param Stock $stock Le stock
     * @param string $quantite La quantité à retirer
     * @return Stock Le stock mis à jour
     * @throws \InvalidArgumentException Si la quantité est insuffisante
     */
    public function decreaseQuantite(Stock $stock, string $quantite): Stock
    {
        $currentQuantite = (float) $stock->getQuantite();
        $removeQuantite = (float) $quantite;
        $newQuantite = $currentQuantite - $removeQuantite;
        
        if ($newQuantite < 0) {
            throw new \InvalidArgumentException(
                'La quantité à retirer dépasse la quantité disponible.'
            );
        }
        
        $stock->setQuantite((string) $newQuantite);
        return $stock;
    }

    /**
     * Vérifie si le stock est vide
     * 
     * @param Stock $stock Le stock
     * @return bool True si le stock est vide
     */
    public function isEmpty(Stock $stock): bool
    {
        return (float) $stock->getQuantite() === 0.0;
    }

    /**
     * Vérifie si le stock est faible (< 10 unités)
     * 
     * @param Stock $stock Le stock
     * @return bool True si le stock est faible
     */
    public function isLow(Stock $stock): bool
    {
        return (float) $stock->getQuantite() < 10;
    }

    /**
     * Obtient le statut du stock
     * 
     * @param Stock $stock Le stock
     * @return string 'expired', 'expiring_soon', 'low', 'empty', ou 'ok'
     */
    public function getStatus(Stock $stock): string
    {
        if ($this->isExpired($stock)) {
            return 'expired';
        }

        if ($this->isExpiringsoon($stock)) {
            return 'expiring_soon';
        }

        if ($this->isEmpty($stock)) {
            return 'empty';
        }

        if ($this->isLow($stock)) {
            return 'low';
        }

        return 'ok';
    }

    /**
     * Obtient le label du statut
     * 
     * @param string $status Le statut
     * @return string Le label
     */
    public function getStatusLabel(string $status): string
    {
        $labels = [
            'expired' => 'Expiré',
            'expiring_soon' => 'Expire bientôt',
            'low' => 'Stock faible',
            'empty' => 'Vide',
            'ok' => 'OK',
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Obtient la couleur du statut pour l'affichage
     * 
     * @param string $status Le statut
     * @return string La couleur (danger, warning, success)
     */
    public function getStatusColor(string $status): string
    {
        return match ($status) {
            'expired' => 'danger',
            'expiring_soon' => 'warning',
            'low' => 'warning',
            'empty' => 'danger',
            'ok' => 'success',
            default => 'secondary',
        };
    }
}
