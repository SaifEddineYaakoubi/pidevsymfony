<?php

namespace App\Service;

use App\Entity\Client;
use App\Entity\Produit;
use App\Enum\ClientBadge;

/**
 * Service pour gérer les calculs de prix avec réductions
 */
class PricingService
{
    public function __construct(
        private ClientBadgeService $badgeService
    ) {
    }

    /**
     * Calcule le prix avec réduction pour un client
     */
    /** @return array<string, mixed> */
    public function calculatePriceWithDiscount(
        float $basePrice, 
        Client $client
    ): array {
        $badge = $this->badgeService->calculateBadge($client);
        $discountPercentage = $badge->getDiscountPercentage();
        $discountAmount = $badge->calculateDiscountAmount($basePrice);
        $finalPrice = $badge->applyDiscount($basePrice);

        return [
            'base_price' => $basePrice,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'final_price' => $finalPrice,
            'badge' => $badge,
            'savings' => $discountAmount,
        ];
    }

    /**
     * Calcule le prix d'un produit avec réduction pour un client
     */
    /** @return array<string, mixed> */
    public function calculateProductPrice(
        Produit $produit, 
        float $quantity, 
        Client $client
    ): array {
        $basePrice = $produit->getPrixUnitaire() * $quantity;
        return $this->calculatePriceWithDiscount($basePrice, $client);
    }

    /**
     * Calcule le total d'une commande avec réduction
     */
    /** @return array<string, mixed> */
    public function calculateOrderTotal(
        array $items, // ['produit' => Produit, 'quantity' => float]
        Client $client
    ): array {
        $subtotal = 0;
        $itemsWithPrices = [];

        foreach ($items as $item) {
            $produit = $item['produit'];
            $quantity = $item['quantity'];
            $itemTotal = $produit->getPrixUnitaire() * $quantity;
            $subtotal += $itemTotal;

            $itemsWithPrices[] = [
                'produit' => $produit,
                'quantity' => $quantity,
                'unit_price' => $produit->getPrixUnitaire(),
                'item_total' => $itemTotal,
            ];
        }

        $badge = $this->badgeService->calculateBadge($client);
        $discountPercentage = $badge->getDiscountPercentage();
        $discountAmount = $badge->calculateDiscountAmount($subtotal);
        $finalTotal = $badge->applyDiscount($subtotal);

        return [
            'items' => $itemsWithPrices,
            'subtotal' => $subtotal,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'final_total' => $finalTotal,
            'badge' => $badge,
            'total_savings' => $discountAmount,
        ];
    }

    /**
     * Formate un prix pour l'affichage
     */
    public function formatPrice(float $price, string $currency = 'TND'): string
    {
        return number_format($price, 2, ',', ' ') . ' ' . $currency;
    }

    /**
     * Génère un résumé de prix pour affichage
     */
    public function generatePriceSummary(float $basePrice, Client $client): string
    {
        $pricing = $this->calculatePriceWithDiscount($basePrice, $client);
        
        if ($pricing['discount_percentage'] > 0) {
            return sprintf(
                '%s (-%s%%) = %s',
                $this->formatPrice($pricing['base_price']),
                $pricing['discount_percentage'],
                $this->formatPrice($pricing['final_price'])
            );
        }

        return $this->formatPrice($pricing['final_price']);
    }
}
