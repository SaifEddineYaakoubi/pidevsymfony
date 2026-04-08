<?php

namespace App\Service\Alert;

use App\Entity\Culture;

/**
 * Centralized business rules for Culture alerts.
 *
 * Rule: if the remaining duration between today and dateRecoltePrevue
 * is less than N days (default 7), the culture is considered at risk.
 */
final class CultureAlertService
{
    public function __construct(
        private readonly int $thresholdDays = 7,
    ) {
    }

    /**
     * @return array<int, array{culture: Culture, days_left: int}>
     */
    public function getHarvestDueSoonAlerts(iterable $cultures, ?\DateTimeImmutable $now = null): array
    {
        $now = $now ?? new \DateTimeImmutable('today');

        $alerts = [];
        foreach ($cultures as $culture) {
            $date = $culture->getDateRecoltePrevue();
            if (!$date) {
                continue;
            }

            $recolte = \DateTimeImmutable::createFromInterface($date)->setTime(0, 0);
            $daysLeft = (int) $now->diff($recolte)->format('%r%a');

            // Only upcoming (0..threshold-1). Past dates are ignored here.
            if ($daysLeft >= 0 && $daysLeft < $this->thresholdDays) {
                $alerts[] = ['culture' => $culture, 'days_left' => $daysLeft];
            }
        }

        // Most urgent first
        usort($alerts, static fn ($a, $b) => $a['days_left'] <=> $b['days_left']);

        return $alerts;
    }

    public function getThresholdDays(): int
    {
        return $this->thresholdDays;
    }
}

