<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class GeoLocationService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private LoggerInterface $logger;
    private const API_BASE_URL = 'https://api.ipgeolocation.io/ipgeo';

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        string $apiKey
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $apiKey;
    }

    /**
     * Récupère la localisation (ville et région) à partir d'une adresse IP
     * 
     * @param string $ip Adresse IP du visiteur
     * @return array ['success' => bool, 'city' => string|null, 'region' => string|null, 'country' => string|null, 'error' => string|null]
     */
    /** @return array<string, mixed> */
    public function getLocation(string $ip): array
    {
        try {
            // Gérer le cas de l'IP locale (127.0.0.1 ou ::1)
            if ($ip === '127.0.0.1' || $ip === '::1' || $ip === 'localhost') {
                $this->logger->info('IP locale détectée, utilisation de 197.0.0.1 pour les tests');
                $ip = '197.0.0.1'; // IP tunisienne pour les tests
            }

            // Construire l'URL de l'API
            $url = sprintf(
                '%s?apiKey=%s&ip=%s',
                self::API_BASE_URL,
                $this->apiKey,
                $ip
            );

            $this->logger->info('Appel API Geolocation', [
                'ip' => $ip,
                'url' => self::API_BASE_URL
            ]);

            // Appel à l'API avec timeout de 5 secondes
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 5,
            ]);

            // Vérifier le code de statut HTTP
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $this->logger->error('API Geolocation returned non-200 status', [
                    'status_code' => $statusCode,
                    'ip' => $ip
                ]);
                
                return [
                    'success' => false,
                    'city' => null,
                    'region' => null,
                    'country' => null,
                    'error' => 'Service de géolocalisation temporairement indisponible'
                ];
            }

            // Décoder la réponse JSON
            $data = $response->toArray();

            $this->logger->info('Réponse API Geolocation', [
                'city' => $data['city'] ?? 'N/A',
                'region' => $data['state_prov'] ?? 'N/A',
                'country' => $data['country_name'] ?? 'N/A'
            ]);

            // Vérifier si les données sont présentes
            if (isset($data['city']) && isset($data['state_prov'])) {
                return [
                    'success' => true,
                    'city' => $data['city'],
                    'region' => $data['state_prov'],
                    'country' => $data['country_name'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'error' => null
                ];
            } else {
                $this->logger->warning('API Geolocation returned incomplete data', [
                    'data' => $data,
                    'ip' => $ip
                ]);
                
                return [
                    'success' => false,
                    'city' => null,
                    'region' => null,
                    'country' => null,
                    'error' => 'Données de localisation incomplètes'
                ];
            }

        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            // Erreur de connexion réseau
            $this->logger->error('Network error calling Geolocation API', [
                'exception' => $e->getMessage(),
                'ip' => $ip
            ]);
            
            return [
                'success' => false,
                'city' => null,
                'region' => null,
                'country' => null,
                'error' => 'Impossible de contacter le service de géolocalisation'
            ];

        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            // Erreur HTTP (4xx, 5xx)
            $this->logger->error('HTTP error calling Geolocation API', [
                'exception' => $e->getMessage(),
                'ip' => $ip
            ]);
            
            return [
                'success' => false,
                'city' => null,
                'region' => null,
                'country' => null,
                'error' => 'Erreur du service de géolocalisation'
            ];

        } catch (\Exception $e) {
            // Toute autre erreur
            $this->logger->error('Unexpected error in GeoLocationService', [
                'exception' => $e->getMessage(),
                'ip' => $ip
            ]);
            
            return [
                'success' => false,
                'city' => null,
                'region' => null,
                'country' => null,
                'error' => 'Erreur inattendue lors de la géolocalisation'
            ];
        }
    }

    /**
     * Calcule les frais de livraison en fonction de la région
     * 
     * @param string $region Région du client
     * @return float Frais de livraison en DT
     */
    public function calculateShippingCost(string $region): float
    {
        // Si la région contient "Tunis", frais de 7 DT, sinon 12 DT
        if (stripos($region, 'Tunis') !== false) {
            return 7.0;
        }
        
        return 12.0;
    }

    /**
     * Récupère la localisation et calcule les frais de livraison
     * 
     * @param string $ip Adresse IP du visiteur
     * @return array ['success' => bool, 'city' => string|null, 'region' => string|null, 'frais_livraison' => float, 'error' => string|null]
     */
    /** @return array<string, mixed> */
    public function getLocationWithShipping(string $ip): array
    {
        $location = $this->getLocation($ip);
        
        if ($location['success']) {
            $fraisLivraison = $this->calculateShippingCost($location['region']);
            
            return [
                'success' => true,
                'city' => $location['city'],
                'region' => $location['region'],
                'country' => $location['country'],
                'frais_livraison' => $fraisLivraison,
                'error' => null
            ];
        }
        
        // En cas d'erreur, retourner des frais par défaut (12 DT)
        return [
            'success' => false,
            'city' => null,
            'region' => null,
            'country' => null,
            'frais_livraison' => 12.0, // Frais par défaut
            'error' => $location['error']
        ];
    }
}
