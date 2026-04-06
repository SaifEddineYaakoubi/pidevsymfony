<?php

namespace App\Twig;

use App\Entity\Client;
use Doctrine\ORM\Proxy\Proxy;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class SafeClientFilter extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('safe_client_name', [$this, 'getSafeClientName']),
            new TwigFilter('safe_client_contact', [$this, 'getSafeClientContact']),
            new TwigFilter('safe_client_adresse', [$this, 'getSafeClientAdresse']),
        ];
    }

    public function getSafeClientName(?Client $client, string $default = 'Client supprimé'): string
    {
        try {
            if ($client === null) {
                return $default;
            }

            // Vérifier si c'est un proxy Doctrine non initialisé
            if ($client instanceof Proxy && !$client->__isInitialized()) {
                return $default;
            }

            return $client->getNom() ?? $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    public function getSafeClientContact(?Client $client, string $default = '-'): string
    {
        try {
            if ($client === null) {
                return $default;
            }

            if ($client instanceof Proxy && !$client->__isInitialized()) {
                return $default;
            }

            return $client->getContact() ?? $default;
        } catch (\Exception $e) {
            return $default;
        }
    }

    public function getSafeClientAdresse(?Client $client, string $default = '-'): string
    {
        try {
            if ($client === null) {
                return $default;
            }

            if ($client instanceof Proxy && !$client->__isInitialized()) {
                return $default;
            }

            return $client->getAdresse() ?? $default;
        } catch (\Exception $e) {
            return $default;
        }
    }
}

