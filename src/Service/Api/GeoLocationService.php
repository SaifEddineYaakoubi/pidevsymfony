<?php

namespace App\Service\Api;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class GeoLocationService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $nominatimUserAgent,
    ) {
    }

    /**
     * @return array{
     *   ok: bool,
     *   source: 'api'|'fallback',
     *   query: string,
     *   lat: float,
     *   lon: float,
     *   display_name: string,
     *   error?: string
     * }
     */
    public function geocode(string $query): array
    {
        $query = trim($query);
        if ($query === '') {
            return $this->fallback('Localisation non fournie');
        }

        $ua = trim($this->nominatimUserAgent);
        if ($ua === '') {
            // Nominatim requires identifying User-Agent
            $ua = 'piweb/1.0 (contact: dev@localhost)';
        }

        try {
            $response = $this->httpClient->request('GET', 'https://nominatim.openstreetmap.org/search', [
                'query' => [
                    'q' => $query,
                    'format' => 'json',
                    'limit' => 1,
                ],
                'headers' => [
                    'User-Agent' => $ua,
                    'Accept' => 'application/json',
                ],
                'timeout' => 7,
            ]);

            $data = $response->toArray(false);
            if (!is_array($data) || $data === []) {
                return $this->fallback('Aucun résultat trouvé');
            }

            $first = $data[0] ?? null;
            if (!is_array($first)) {
                return $this->fallback('Réponse API invalide');
            }

            $lat = isset($first['lat']) ? (float) $first['lat'] : 0.0;
            $lon = isset($first['lon']) ? (float) $first['lon'] : 0.0;
            $displayName = (string) ($first['display_name'] ?? $query);

            return [
                'ok' => true,
                'source' => 'api',
                'query' => $query,
                'lat' => $lat,
                'lon' => $lon,
                'display_name' => $displayName,
            ];
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|DecodingExceptionInterface $e) {
            $this->logger->warning('GeoLocationService failed', [
                'query' => $query,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->fallback('Impossible de géocoder (réseau/API)');
        }
    }

    /**
     * @return array{ok: bool, source: 'fallback', query: string, lat: float, lon: float, display_name: string, error?: string}
     */
    private function fallback(string $reason): array
    {
        return [
            'ok' => false,
            'source' => 'fallback',
            'query' => '—',
            // Default center: Tunis-ish
            'lat' => 36.8065,
            'lon' => 10.1815,
            'display_name' => 'Localisation par défaut',
            'error' => $reason,
        ];
    }
}

