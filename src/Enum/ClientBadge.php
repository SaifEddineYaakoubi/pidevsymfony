<?php

namespace App\Enum;

/**
 * Enum pour les badges clients basés sur le nombre de ventes
 */
enum ClientBadge: string
{
    case GOLD = 'gold';
    case SILVER = 'silver';
    case BRONZE = 'bronze';
    case NONE = 'none';

    /**
     * Retourne le label français du badge
     */
    public function getLabel(): string
    {
        return match($this) {
            self::GOLD => 'Or',
            self::SILVER => 'Argent',
            self::BRONZE => 'Bronze',
            self::NONE => 'Aucun',
        };
    }

    /**
     * Retourne l'icône du badge
     */
    public function getIcon(): string
    {
        return match($this) {
            self::GOLD => '🥇',
            self::SILVER => '🥈',
            self::BRONZE => '🥉',
            self::NONE => '⚪',
        };
    }

    /**
     * Retourne la classe CSS Bootstrap pour le badge
     */
    public function getCssClass(): string
    {
        return match($this) {
            self::GOLD => 'badge bg-warning text-dark',
            self::SILVER => 'badge bg-secondary',
            self::BRONZE => 'badge bg-danger',
            self::NONE => 'badge bg-light text-dark',
        };
    }

    /**
     * Retourne le pourcentage de réduction associé au badge
     */
    public function getDiscountPercentage(): float
    {
        return match($this) {
            self::GOLD => 15.0,    // 15% de réduction pour les clients Gold
            self::SILVER => 10.0,  // 10% de réduction pour les clients Silver
            self::BRONZE => 5.0,   // 5% de réduction pour les clients Bronze
            self::NONE => 0.0,     // Pas de réduction
        };
    }

    /**
     * Calcule le montant après réduction
     */
    public function applyDiscount(float $amount): float
    {
        $discount = $this->getDiscountPercentage();
        return $amount * (1 - ($discount / 100));
    }

    /**
     * Calcule le montant de la réduction
     */
    public function calculateDiscountAmount(float $amount): float
    {
        $discount = $this->getDiscountPercentage();
        return $amount * ($discount / 100);
    }

    /**
     * Retourne une description de l'avantage du badge
     */
    public function getBenefitDescription(): string
    {
        return match($this) {
            self::GOLD => 'Réduction de 15% sur tous les achats + Livraison gratuite',
            self::SILVER => 'Réduction de 10% sur tous les achats',
            self::BRONZE => 'Réduction de 5% sur tous les achats',
            self::NONE => 'Aucun avantage pour le moment',
        };
    }

    /**
     * Détermine le badge basé sur le nombre de ventes
     */
    public static function fromVenteCount(int $venteCount): self
    {
        return match(true) {
            $venteCount >= 3 => self::GOLD,
            $venteCount === 2 => self::SILVER,
            $venteCount === 1 => self::BRONZE,
            default => self::NONE,
        };
    }
}
