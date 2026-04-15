<?php

namespace App\Service\Api;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WeatherService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $openWeatherApiKey,
    ) {
    }

    /**
     * @return array{
     *   ok: bool,
     *   source: 'api'|'fallback',
     *   city: string,
     *   temperature: float,
     *   humidity: int,
     *   windSpeed: float,
     *   mainCondition: string,
     *   description: string,
     *   advice: string,
     *   icon?: string,
     *   error?: string
     * }
     */
    public function getCurrentWeatherByCity(string $city): array
    {
        $city = trim($city);
        if ($city === '') {
            return $this->fallback('Ville non fournie');
        }

        if ($this->openWeatherApiKey === '' || $this->openWeatherApiKey === 'CHANGE_ME') {
            return $this->fallback('Clé API OpenWeatherMap manquante');
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'q' => $city,
                    'appid' => $this->openWeatherApiKey,
                    'units' => 'metric',
                    'lang' => 'fr',
                ],
                'timeout' => 7,
            ]);

            $status = $response->getStatusCode();
            $data = $response->toArray(false);

            // OpenWeatherMap returns 404 with JSON body {"cod":"404","message":"city not found"}
            if ($status >= 400) {
                $message = is_array($data) && isset($data['message']) ? (string) $data['message'] : 'Erreur API météo';
                return $this->fallback($message);
            }

            $parsed = $this->parseWeather($data, $city);
            $parsed['advice'] = $this->makeAgricultureAdvice($parsed);

            return $parsed;
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|DecodingExceptionInterface $e) {
            $this->logger->warning('WeatherService failed', [
                'city' => $city,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->fallback('Impossible de récupérer la météo (réseau/API)');
        }
    }

    /**
     * @return array{
     *   ok: bool,
     *   source: 'api'|'fallback',
     *   city: string,
     *   temperature: float,
     *   humidity: int,
     *   windSpeed: float,
     *   mainCondition: string,
     *   description: string,
     *   advice: string,
     *   icon?: string,
     *   error?: string
     * }
     */
    public function getCurrentWeatherByCoordinates(float $lat, float $lon): array
    {
        if ($this->openWeatherApiKey === '' || $this->openWeatherApiKey === 'CHANGE_ME') {
            return $this->fallback('Clé API OpenWeatherMap manquante');
        }

        // clamp to safe ranges
        if ($lat < -90 || $lat > 90 || $lon < -180 || $lon > 180) {
            return $this->fallback('Coordonnées invalides');
        }

        try {
            $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                'query' => [
                    'lat' => $lat,
                    'lon' => $lon,
                    'appid' => $this->openWeatherApiKey,
                    'units' => 'metric',
                    'lang' => 'fr',
                ],
                'timeout' => 7,
            ]);

            $status = $response->getStatusCode();
            $data = $response->toArray(false);
            if ($status >= 400) {
                $message = is_array($data) && isset($data['message']) ? (string) $data['message'] : 'Erreur API météo';
                return $this->fallback($message);
            }

            $parsed = $this->parseWeather($data, sprintf('%.4f, %.4f', $lat, $lon));
            $parsed['advice'] = $this->makeAgricultureAdvice($parsed);

            return $parsed;
        } catch (TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|DecodingExceptionInterface $e) {
            $this->logger->warning('WeatherService failed (coords)', [
                'lat' => $lat,
                'lon' => $lon,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return $this->fallback('Impossible de récupérer la météo (réseau/API)');
        }
    }

    /**
     * @param array<string,mixed> $data
     * @return array{ok: bool, source: 'api', city: string, temperature: float, humidity: int, windSpeed: float, mainCondition: string, description: string, advice: string, icon?: string}
     */
    private function parseWeather(array $data, string $fallbackCityName): array
    {
        $cityName = (string) ($data['name'] ?? $fallbackCityName);
        $temp = (float) ($data['main']['temp'] ?? 0);
        $humidity = (int) ($data['main']['humidity'] ?? 0);
        $windSpeed = (float) ($data['wind']['speed'] ?? 0);

        $mainCondition = '';
        $description = '';
        $icon = null;
        if (isset($data['weather'][0]) && is_array($data['weather'][0])) {
            $mainCondition = (string) ($data['weather'][0]['main'] ?? '');
            $description = (string) ($data['weather'][0]['description'] ?? '');
            $icon = isset($data['weather'][0]['icon']) ? (string) $data['weather'][0]['icon'] : null;
        }

        $out = [
            'ok' => true,
            'source' => 'api',
            'city' => $cityName,
            'temperature' => $temp,
            'humidity' => $humidity,
            'windSpeed' => $windSpeed,
            'mainCondition' => $mainCondition,
            'description' => $description,
            'advice' => '',
        ];
        if ($icon) {
            $out['icon'] = $icon;
        }

        return $out;
    }

    /**
     * @param array{temperature: float, humidity: int, windSpeed: float, mainCondition: string, description: string} $w
     */
    private function makeAgricultureAdvice(array $w): string
    {
        $temp = $w['temperature'];
        $humidity = $w['humidity'];
        $wind = $w['windSpeed'];
        $cond = mb_strtolower($w['mainCondition'] ?? '');
        $desc = mb_strtolower($w['description'] ?? '');

        $tips = [];

        // Temp rules
        if ($temp > 35) {
            $tips[] = 'Chaleur extrême : arrosage intensif recommandé et éviter les travaux en plein soleil.';
        } elseif ($temp > 30) {
            $tips[] = 'Température élevée : arroser tôt le matin ou tard le soir.';
        } elseif ($temp < 0) {
            $tips[] = 'Risque de gel : protégez les cultures (voiles, paillage) et limitez l’irrigation.';
        }

        // Rain / sky
        if (str_contains($cond, 'rain') || str_contains($desc, 'pluie') || str_contains($cond, 'drizzle') || str_contains($cond, 'thunder')) {
            $tips[] = 'Pluie : pas besoin d’arroser, surveillez les risques d’inondation.';
        } elseif (str_contains($cond, 'clear') || str_contains($desc, 'ciel dégagé')) {
            $tips[] = 'Ciel dégagé : surveillez l’évaporation et l’humidité du sol.';
        }

        // Humidity
        if ($humidity > 80) {
            $tips[] = 'Humidité élevée : risque de maladies fongiques — aérez et surveillez les feuilles.';
        } elseif ($humidity < 25) {
            $tips[] = 'Air sec : surveillez le stress hydrique et privilégiez le paillage.';
        }

        // Wind
        if ($wind >= 12) {
            $tips[] = 'Vent fort : protégez les cultures fragiles et évitez les traitements pulvérisés.';
        }

        if ($tips === []) {
            return 'Conditions favorables : continuez l’entretien habituel et surveillez l’irrigation selon le sol.';
        }

        return implode(' ', $tips);
    }

    /**
     * @return array{ok: bool, source: 'fallback', city: string, temperature: float, humidity: int, windSpeed: float, mainCondition: string, description: string, advice: string, error?: string}
     */
    private function fallback(string $reason): array
    {
        // Simple simulated dataset
        $data = [
            'ok' => false,
            'source' => 'fallback',
            'city' => '—',
            'temperature' => 24.0,
            'humidity' => 55,
            'windSpeed' => 3.0,
            'mainCondition' => 'Clear',
            'description' => 'données simulées',
            'advice' => 'Données météo indisponibles : appliquez une irrigation prudente et surveillez l’humidité du sol.',
            'error' => $reason,
        ];

        return $data;
    }
}

