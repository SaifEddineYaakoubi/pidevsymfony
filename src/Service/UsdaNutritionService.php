<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UsdaNutritionService
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
        $this->apiKey = $_ENV['USDA_API_KEY'] ?? '';
    }

    /**
     * Recherche les informations nutritionnelles d'un produit depuis l'API USDA.
     */
    public function getNutrition(string $productName): ?array
    {
        $cacheKey = $this->getCacheKey($productName);

        $nutrition = $this->cache->get($cacheKey, function (ItemInterface $item) use ($productName) {
            $item->expiresAfter(604800); // 7 jours

            return $this->fetchUsdaNutritionData($productName);
        });

        if ($nutrition === null) {
            $this->cache->delete($cacheKey);
        }

        return $nutrition;
    }

    /**
     * Supprime le cache de recherche pour ce produit.
     */
    public function clearNutritionCache(string $productName): void
    {
        $this->cache->delete($this->getCacheKey($productName));
    }

    private function fetchUsdaNutritionData(string $productName): ?array
    {
        if (empty($productName)) {
            $this->logger->warning('UsdaNutritionService: nom de produit vide fourni.');
            return null;
        }

        if (empty($this->apiKey)) {
            $this->logger->error('UsdaNutritionService: clé USDA_API_KEY non configurée.');
            return null;
        }

        $queries = $this->buildSearchCandidates($productName);

        foreach ($queries as $query) {
            try {
                $response = $this->httpClient->request('GET', 'https://api.nal.usda.gov/fdc/v1/foods/search', [
                    'query' => [
                        'api_key' => $this->apiKey,
                        'query' => $query,
                        'pageSize' => 1,
                    ],
                    'timeout' => 15,
                ]);

                $data = $response->toArray(false);

                if (!empty($data['errors'])) {
                    $this->logger->warning(sprintf('UsdaNutritionService: erreur USDA pour «%s»: %s', $query, json_encode($data['errors'])));
                    continue;
                }

                if (empty($data['foods'][0])) {
                    $this->logger->info(sprintf('UsdaNutritionService: aucun résultat USDA pour «%s».', $query));
                    continue;
                }

                $food = $data['foods'][0];
                $nutrients = $this->extractNutrients($food['foodNutrients'] ?? []);

                return [
                    'nom' => $food['description'] ?? $productName,
                    'calories' => $nutrients['calories'] ?? 0,
                    'proteines' => $nutrients['proteines'] ?? 0,
                    'glucides' => $nutrients['glucides'] ?? 0,
                    'lipides' => $nutrients['lipides'] ?? 0,
                    'fibres' => $nutrients['fibres'] ?? 0,
                    'sucres' => $nutrients['sucres'] ?? 0,
                    'sodium' => $nutrients['sodium'] ?? 0,
                    'vitamine_c' => $nutrients['vitamine_c'] ?? 0,
                    'fer' => $nutrients['fer'] ?? 0,
                    'calcium' => $nutrients['calcium'] ?? 0,
                    'portion' => '100g',
                ];
            } catch (\Throwable $exception) {
                $this->logger->warning(sprintf('UsdaNutritionService: erreur lors de la requête USDA pour «%s»: %s', $query, $exception->getMessage()));
                continue;
            }
        }

        $this->logger->info(sprintf('UsdaNutritionService: aucun résultat après tentative pour «%s».', $productName));
        return null;
    }

    private function extractNutrients(array $foodNutrients): array
    {
        $nutrients = [];

        $nutrientMap = [
            'Energy' => 'calories', // kcal
            'Protein' => 'proteines', // g
            'Carbohydrate, by difference' => 'glucides', // g
            'Total lipid (fat)' => 'lipides', // g
            'Fiber, total dietary' => 'fibres', // g
            'Dietary fiber' => 'fibres', // g
            'Sugars, total including NLEA' => 'sucres', // g
            'Total Sugars' => 'sucres', // g
            'Sodium, Na' => 'sodium', // mg
            'Vitamin C, total ascorbic acid' => 'vitamine_c', // mg
            'Iron, Fe' => 'fer', // mg
            'Calcium, Ca' => 'calcium', // mg
        ];

        foreach ($foodNutrients as $nutrient) {
            $name = $nutrient['nutrientName'] ?? '';
            $value = $nutrient['value'] ?? 0;
            $unit = $nutrient['unitName'] ?? '';

            foreach ($nutrientMap as $usdaName => $key) {
                if (str_contains($name, $usdaName)) {
                    $nutrients[$key] = $value;
                    break;
                }
            }
        }

        return $nutrients;
    }

    private function buildSearchCandidates(string $productName): array
    {
        $normalized = mb_strtolower(trim($productName));
        $normalized = $this->removeAccents($normalized);

        $translationMap = [
            'tomate' => 'tomato',
            'carotte' => 'carrot',
            'pomme' => 'apple',
            'orange' => 'orange',
            'banane' => 'banana',
            'huile d\'olive' => 'olive oil',
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
            'brocoli' => 'broccoli',
            'ble dur' => 'durum wheat',
            'citrons beldi' => 'lemon',
            'clementines' => 'clementine',
        ];

        $queries = [];
        foreach ($translationMap as $keyword => $english) {
            if (str_contains($normalized, $keyword)) {
                $queries[] = $english;
                $queries[] = $english . ' fresh food';
                $queries[] = $english . ' nutrition';
                $queries[] = $english . ' fruit';
                $queries[] = $keyword;
                $queries[] = $keyword . ' fresh food';
            }
        }

        $queries[] = $normalized;
        $queries[] = $normalized . ' fresh food';
        $queries[] = $normalized . ' nutrition';
        $queries[] = $normalized . ' fruit';
        $queries[] = 'fresh ' . $normalized;
        $queries[] = 'fresh ' . ($translationMap[$normalized] ?? $normalized);

        $queries = array_unique(array_filter(array_map('trim', $queries)));

        return $queries;
    }

    private function removeAccents(string $text): string
    {
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT', $text);
        return $normalized === false ? $text : $normalized;
    }

    private function getCacheKey(string $productName): string
    {
        return 'usda_' . $this->slugify($productName);
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
        $text = trim($text, '-');
        return $text !== '' ? $text : 'unknown';
    }
}
