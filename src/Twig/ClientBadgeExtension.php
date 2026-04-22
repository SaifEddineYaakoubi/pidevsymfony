<?php

namespace App\Twig;

use App\Entity\Client;
use App\Enum\ClientBadge;
use App\Service\ClientBadgeService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Extension Twig pour afficher les badges clients
 */
class ClientBadgeExtension extends AbstractExtension
{
    public function __construct(
        private ClientBadgeService $badgeService
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('client_badge', [$this, 'getClientBadge']),
            new TwigFilter('badge_html', [$this, 'renderBadgeHtml'], ['is_safe' => ['html']]),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('client_badge', [$this, 'getClientBadge']),
            new TwigFunction('badge_html', [$this, 'renderBadgeHtml'], ['is_safe' => ['html']]),
            new TwigFunction('badge_stats', [$this, 'getBadgeStatistics']),
            new TwigFunction('client_discount', [$this, 'getClientDiscount']),
            new TwigFunction('price_with_discount', [$this, 'calculatePriceWithDiscount']),
        ];
    }

    /**
     * Récupère le badge d'un client
     */
    public function getClientBadge(Client $client): ClientBadge
    {
        return $this->badgeService->calculateBadge($client);
    }

    /**
     * Génère le HTML pour afficher un badge
     */
    public function renderBadgeHtml(Client $client, bool $showLabel = true): string
    {
        $badge = $this->badgeService->calculateBadge($client);
        
        $icon = $badge->getIcon();
        $label = $showLabel ? ' ' . $badge->getLabel() : '';
        $cssClass = $badge->getCssClass();
        
        return sprintf(
            '<span class="%s">%s%s</span>',
            $cssClass,
            $icon,
            $label
        );
    }

    /**
     * Récupère les statistiques des badges
     */
    public function getBadgeStatistics(): array
    {
        return $this->badgeService->getBadgeStatistics();
    }

    /**
     * Récupère le pourcentage de réduction d'un client
     */
    public function getClientDiscount(Client $client): float
    {
        $badge = $this->badgeService->calculateBadge($client);
        return $badge->getDiscountPercentage();
    }

    /**
     * Calcule le prix avec réduction pour un client
     */
    public function calculatePriceWithDiscount(float $price, Client $client): array
    {
        $badge = $this->badgeService->calculateBadge($client);
        return [
            'original' => $price,
            'discount_percentage' => $badge->getDiscountPercentage(),
            'discount_amount' => $badge->calculateDiscountAmount($price),
            'final' => $badge->applyDiscount($price),
        ];
    }
}
