<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class CurrencyService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;
    private LoggerInterface $logger;
    private const API_BASE_URL = 'https://v6.exchangerate-api.com/v6';

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
     * Convertit un montant de TND vers EUR
     * 
     * @param float $montantTND Montant en Dinars Tunisiens
     * @return array ['success' => bool, 'montant_eur' => float|null, 'taux' => float|null, 'error' => string|null]
     */
    /** @return array<string, mixed> */
    public function convertTNDtoEUR(float $montantTND): array
    {
        try {
            // Construire l'URL de l'API
            $url = sprintf(
                '%s/%s/pair/TND/EUR/%s',
                self::API_BASE_URL,
                $this->apiKey,
                $montantTND
            );

            // Appel à l'API avec timeout de 5 secondes
            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 5,
            ]);

            // Vérifier le code de statut HTTP
            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $this->logger->error('ExchangeRate API returned non-200 status', [
                    'status_code' => $statusCode,
                    'montant' => $montantTND
                ]);
                
                return [
                    'success' => false,
                    'montant_eur' => null,
                    'taux' => null,
                    'error' => 'Service de conversion temporairement indisponible'
                ];
            }

            // Décoder la réponse JSON
            $data = $response->toArray();

            // Vérifier si la conversion a réussi
            if (isset($data['result']) && $data['result'] === 'success') {
                return [
                    'success' => true,
                    'montant_eur' => $data['conversion_result'] ?? null,
                    'taux' => $data['conversion_rate'] ?? null,
                    'error' => null
                ];
            } else {
                $this->logger->warning('ExchangeRate API returned unsuccessful result', [
                    'data' => $data,
                    'montant' => $montantTND
                ]);
                
                return [
                    'success' => false,
                    'montant_eur' => null,
                    'taux' => null,
                    'error' => 'Conversion échouée'
                ];
            }

        } catch (\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface $e) {
            // Erreur de connexion réseau
            $this->logger->error('Network error calling ExchangeRate API', [
                'exception' => $e->getMessage(),
                'montant' => $montantTND
            ]);
            
            return [
                'success' => false,
                'montant_eur' => null,
                'taux' => null,
                'error' => 'Impossible de contacter le service de conversion'
            ];

        } catch (\Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface $e) {
            // Erreur HTTP (4xx, 5xx)
            $this->logger->error('HTTP error calling ExchangeRate API', [
                'exception' => $e->getMessage(),
                'montant' => $montantTND
            ]);
            
            return [
                'success' => false,
                'montant_eur' => null,
                'taux' => null,
                'error' => 'Erreur du service de conversion'
            ];

        } catch (\Exception $e) {
            // Toute autre erreur
            $this->logger->error('Unexpected error in CurrencyService', [
                'exception' => $e->getMessage(),
                'montant' => $montantTND
            ]);
            
            return [
                'success' => false,
                'montant_eur' => null,
                'taux' => null,
                'error' => 'Erreur inattendue lors de la conversion'
            ];
        }
    }

    /**
     * Récupère le taux de change actuel TND/EUR
     * 
     * @return array ['success' => bool, 'taux' => float|null, 'error' => string|null]
     */
    /** @return array<string, mixed> */
    public function getTauxTNDtoEUR(): array
    {
        try {
            $url = sprintf(
                '%s/%s/latest/TND',
                self::API_BASE_URL,
                $this->apiKey
            );

            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 5,
            ]);

            $data = $response->toArray();

            if (isset($data['result']) && $data['result'] === 'success') {
                $tauxEUR = $data['conversion_rates']['EUR'] ?? null;
                
                return [
                    'success' => true,
                    'taux' => $tauxEUR,
                    'error' => null
                ];
            }

            return [
                'success' => false,
                'taux' => null,
                'error' => 'Impossible de récupérer le taux'
            ];

        } catch (\Exception $e) {
            $this->logger->error('Error getting exchange rate', [
                'exception' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'taux' => null,
                'error' => 'Erreur lors de la récupération du taux'
            ];
        }
    }

    /**
     * Convertit un montant de TND vers USD
     * 
     * @param float $montantTND Montant en Dinars Tunisiens
     * @return array ['success' => bool, 'montant_usd' => float|null, 'taux' => float|null, 'error' => string|null]
     */
    /** @return array<string, mixed> */
    public function convertTNDtoUSD(float $montantTND): array
    {
        try {
            $url = sprintf(
                '%s/%s/pair/TND/USD/%s',
                self::API_BASE_URL,
                $this->apiKey,
                $montantTND
            );

            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 5,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $this->logger->error('ExchangeRate API returned non-200 status for USD', [
                    'status_code' => $statusCode,
                    'montant' => $montantTND
                ]);
                
                return [
                    'success' => false,
                    'montant_usd' => null,
                    'taux' => null,
                    'error' => 'Service de conversion temporairement indisponible'
                ];
            }

            $data = $response->toArray();

            if (isset($data['result']) && $data['result'] === 'success') {
                return [
                    'success' => true,
                    'montant_usd' => $data['conversion_result'] ?? null,
                    'taux' => $data['conversion_rate'] ?? null,
                    'error' => null
                ];
            } else {
                $this->logger->warning('ExchangeRate API returned unsuccessful result for USD', [
                    'data' => $data,
                    'montant' => $montantTND
                ]);
                
                return [
                    'success' => false,
                    'montant_usd' => null,
                    'taux' => null,
                    'error' => 'Conversion échouée'
                ];
            }

        } catch (\Exception $e) {
            $this->logger->error('Error converting TND to USD', [
                'exception' => $e->getMessage(),
                'montant' => $montantTND
            ]);
            
            return [
                'success' => false,
                'montant_usd' => null,
                'taux' => null,
                'error' => 'Erreur lors de la conversion'
            ];
        }
    }

    /**
     * Récupère tous les taux de change pour TND (EUR et USD)
     * 
     * @return array ['success' => bool, 'taux' => array, 'error' => string|null]
     */
    /** @return array<string, mixed> */
    public function getAllRates(): array
    {
        try {
            $url = sprintf(
                '%s/%s/latest/TND',
                self::API_BASE_URL,
                $this->apiKey
            );

            $response = $this->httpClient->request('GET', $url, [
                'timeout' => 5,
            ]);

            $data = $response->toArray();

            if (isset($data['result']) && $data['result'] === 'success') {
                return [
                    'success' => true,
                    'taux' => [
                        'EUR' => $data['conversion_rates']['EUR'] ?? null,
                        'USD' => $data['conversion_rates']['USD'] ?? null,
                    ],
                    'date_maj' => $data['time_last_update_utc'] ?? null,
                    'error' => null
                ];
            }

            return [
                'success' => false,
                'taux' => null,
                'error' => 'Impossible de récupérer les taux'
            ];

        } catch (\Exception $e) {
            $this->logger->error('Error getting all exchange rates', [
                'exception' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'taux' => null,
                'error' => 'Erreur lors de la récupération des taux'
            ];
        }
    }

    /**
     * Convertit un montant TND vers plusieurs devises (EUR et USD)
     * 
     * @param float $montantTND Montant en Dinars Tunisiens
     * @return array
     */
    /** @return array<string, mixed> */
    public function convertTNDtoMultiple(float $montantTND): array
    {
        $rates = $this->getAllRates();
        
        if (!$rates['success']) {
            return [
                'success' => false,
                'conversions' => null,
                'error' => $rates['error']
            ];
        }

        return [
            'success' => true,
            'montant_tnd' => $montantTND,
            'conversions' => [
                'EUR' => [
                    'montant' => $montantTND * $rates['taux']['EUR'],
                    'taux' => $rates['taux']['EUR']
                ],
                'USD' => [
                    'montant' => $montantTND * $rates['taux']['USD'],
                    'taux' => $rates['taux']['USD']
                ]
            ],
            'date_maj' => $rates['date_maj'],
            'error' => null
        ];
    }
}
