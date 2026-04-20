<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PexelsImageService
{
    private HttpClientInterface $httpClient;
    private CacheInterface $cache;
    private LoggerInterface $logger;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, CacheInterface $cache, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
        $this->logger = $logger;
        $this->apiKey = $_ENV['PEXELS_API_KEY'] ?? '';
    }

    /**
     * Recherche une image de produit sur Pexels et retourne l'URL régulière.
     */
    public function searchProductImage(string $productName): ?string
    {
        $imageData = $this->searchProductImageDetails($productName);

        return $imageData['url'] ?? null;
    }

    /**
     * Recherche une image de produit et retourne les détails utiles pour le front.
     */
    public function searchProductImageDetails(string $productName): ?array
    {
        $cacheKey = $this->getCacheKey($productName);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($productName) {
            $item->expiresAfter(86400);

            return $this->fetchPexelsImageData($productName);
        });
    }

    /**
     * Supprime le cache de recherche pour ce produit.
     */
    public function clearProductImageCache(string $productName): void
    {
        $this->cache->delete($this->getCacheKey($productName));
    }

    private function fetchPexelsImageData(string $productName): ?array
    {
        if (empty($productName)) {
            $this->logger->warning('PexelsImageService: nom de produit vide fourni.');
            return null;
        }

        if (empty($this->apiKey)) {
            $this->logger->error('PexelsImageService: clé PEXELS_API_KEY non configurée.');
            return null;
        }

        $queries = $this->buildSearchCandidates($productName);
        foreach ($queries as $query) {
            try {
                $response = $this->httpClient->request('GET', 'https://api.pexels.com/v1/search', [
                    'query' => [
                        'query' => $query,
                        'per_page' => 1,
                        'orientation' => 'square',
                        'size' => 'medium',
                    ],
                    'headers' => [
                        'Authorization' => $this->apiKey,
                    ],
                    'timeout' => 10,
                ]);

                $data = $response->toArray(false);
                if (!empty($data['error'])) {
                    $this->logger->warning(sprintf('PexelsImageService: erreur API Pexels pour «%s»: %s', $query, $data['error']));
                    continue;
                }

                if (empty($data['photos'][0]['src']['large'])) {
                    $this->logger->info(sprintf('PexelsImageService: aucune image trouvée pour «%s».', $query));
                    continue;
                }

                $photo = $data['photos'][0];

                return [
                    'url' => $photo['src']['large'],
                    'photographer' => $photo['photographer'],
                    'photographerUrl' => $photo['photographer_url']
                ];
            } catch (\Throwable $exception) {
                $this->logger->warning(sprintf('PexelsImageService: erreur lors de la requête Pexels pour «%s»: %s', $query, $exception->getMessage()));
                continue;
            }
        }

        $this->logger->info(sprintf('PexelsImageService: aucune image trouvée après tentative pour «%s».', $productName));
        return null;
    }

    private function buildSearchCandidates(string $productName): array
    {
        $normalized = mb_strtolower(trim($productName));

        $translationMap = [
            'tomate' => 'tomato',
            'carotte' => 'carrot',
            'pomme' => 'apple',
            'orange' => 'orange fruit',
            'banane' => 'banana',
            'huile olive' => 'olive oil',
            'ail' => 'garlic',
            'oignon' => 'onion',
            'pomme de terre' => 'potato',
            'courgette' => 'zucchini',
            'aubergine' => 'eggplant',
            'poivron' => 'bell pepper',
            'citron' => 'lemon',
            'fraise' => 'strawberry',
            'raisin' => 'grapes',
            'poire' => 'pear',
            'peche' => 'peach',
            'abricot' => 'apricot',
            'figue' => 'fig',
            'datte' => 'dates',
            'olive' => 'olives',
            'epinard' => 'spinach',
            'laitue' => 'lettuce',
            'concombre' => 'cucumber',
            'brocoli' => 'broccoli'
        ];

        $queries = [];
        foreach ($translationMap as $keyword => $english) {
            if (str_contains($normalized, $keyword)) {
                $queries[] = $english;
            }
        }

        $queries[] = $productName . ' fresh food';

        $queries = array_unique(array_filter(array_map('trim', $queries)));

        return $queries;
    }

    private function getCacheKey(string $productName): string
    {
        return 'pexels_' . $this->slugify($productName);
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
        $text = trim($text, '-');
        return $text !== '' ? $text : 'unknown';
    }
}