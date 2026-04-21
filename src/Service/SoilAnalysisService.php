<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Recolte;
use App\Entity\Rendement;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service pour analyser qualité du sol et corréler avec récolte/rendement
 * Utilise AgroAPI pour obtenir données NPK, pH, humidité du sol
 */
class SoilAnalysisService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private CacheInterface $cache;
    private string $apiKey;
    private const CACHE_TTL = 86400; // 24 heures

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger,
        CacheInterface $cache,
        string $agroApiKey
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->cache = $cache;
        $this->apiKey = $agroApiKey;
    }

    /**
     * Obtenir analyse complète du sol pour une récolte
     *
     * @param Recolte $recolte
     * @return array Données NPK, pH, humidité, recommandations
     */
    public function getSoilAnalysisForRecolte(Recolte $recolte): array
    {
        $cacheKey = 'soil_analysis_' . md5($recolte->getLocalisation());

        try {
            // Essayer de récupérer du cache
            return $this->cache->get($cacheKey, function ($item) use ($recolte) {
                $item->expiresAfter(self::CACHE_TTL);

                $soilData = $this->fetchSoilDataFromAPI($recolte->getLocalisation());

                return [
                    'npk' => [
                        'nitrogen' => $soilData['nitrogen'] ?? 0,
                        'phosphorus' => $soilData['phosphorus'] ?? 0,
                        'potassium' => $soilData['potassium'] ?? 0,
                    ],
                    // Aliases for test_recolte template
                    'npk_analysis' => [
                        'nitrogen' => $soilData['nitrogen'] ?? 0,
                        'phosphorus' => $soilData['phosphorus'] ?? 0,
                        'potassium' => $soilData['potassium'] ?? 0,
                    ],
                    'ph' => $soilData['ph'] ?? 0,
                    'humidity' => $soilData['humidity'] ?? 0,
                    'soil_type' => $soilData['soil_type'] ?? 'Unknown',
                    'soil_quality' => $this->calculateSoilQuality($soilData),
                    'score_global' => $this->calculateSoilQuality($soilData),
                    // Aliases for test_recolte template
                    'soil_properties' => [
                        'ph' => $soilData['ph'] ?? 0,
                        'humidity' => $soilData['humidity'] ?? 0,
                        'organic_matter' => $soilData['organic_matter'] ?? 0,
                        'soil_type' => $soilData['soil_type'] ?? 'Unknown',
                    ],
                    'recommendations' => $this->generateRecommendations($soilData, $recolte),
                    'harvest_compatibility' => $this->analyzeCompatibilityWithHarvest($soilData, $recolte),
                    'api_source' => $soilData['api_source'] ?? 'unknown',
                    'region_profile' => $soilData['region_profile'] ?? null,
                ];
            });
        } catch (\Exception $e) {
            $this->logger->error('Erreur API SoilAnalysis: ' . $e->getMessage());
            return ['error' => 'Impossible de récupérer les données du sol'];
        }
    }

    /**
     * Analyser impact du sol sur le rendement obtenu
     *
     * @param Rendement $rendement
     * @return array Analyse impact sol/rendement
     */
    public function analyzeImpactOnYield(Rendement $rendement): array
    {
        $recolte = $rendement->getId_recolte();
        if (!$recolte) {
            return ['error' => 'Récolte non associée au rendement'];
        }

        $soilAnalysis = $this->getSoilAnalysisForRecolte($recolte);

        if (isset($soilAnalysis['error'])) {
            return $soilAnalysis;
        }

        $productivite = $rendement->getProductivite();
        $soilQuality = $soilAnalysis['soil_quality'];

        return [
            'soil_quality_score' => $soilQuality,
            'productivity' => $productivite,
            'correlation' => $this->calculateCorrelation($soilQuality, $productivite),
            'soil_factors_affecting_yield' => $this->identifyLimitingFactors($soilAnalysis),
            'recommendations_to_improve' => $this->recommendImprovements($soilAnalysis, $productivite),
            'predicted_yield_potential' => $this->predictYieldPotential($soilAnalysis),
            'actual_vs_potential' => [
                'actual' => $productivite,
                'potential' => $this->predictYieldPotential($soilAnalysis),
                'efficiency' => round(($productivite / $this->predictYieldPotential($soilAnalysis)) * 100, 2) . '%'
            ],
            'api_source' => $soilAnalysis['api_source'] ?? 'unknown',
            'region_profile' => $soilAnalysis['region_profile'] ?? null
        ];
    }

    /**
     * Récupérer données du sol depuis AgroAPI
     */
    private function fetchSoilDataFromAPI(string $location): array
    {
        try {
            // Vérifier si la clé API est configurée
            if (empty($this->apiKey) || $this->apiKey === 'your_agro_api_key_here') {
                $this->logger->warning('AGRO_API_KEY non configurée, utilisation de données de test');
                return $this->getTestSoilData($location);
            }

            // Appel API AgroAPI
            $response = $this->httpClient->request('GET', 'https://api.agroapi.com/v1/soil-analysis', [
                'query' => [
                    'location' => $location,
                    'api_key' => $this->apiKey,
                ],
                'timeout' => 10, // Timeout de 10 secondes
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $this->logger->warning("API AgroAPI retournée status $statusCode pour location: $location");
                return $this->getTestSoilData($location);
            }

            $data = $response->toArray();

            // Vérifier si les données sont vides ou nulles
            if (empty($data) || (!isset($data['npk']) && !isset($data['ph']) && !isset($data['humidity']))) {
                $this->logger->warning("API AgroAPI retournée des données vides pour location: $location");
                return $this->getTestSoilData($location);
            }

            return [
                'nitrogen' => $data['npk']['nitrogen'] ?? 0,
                'phosphorus' => $data['npk']['phosphorus'] ?? 0,
                'potassium' => $data['npk']['potassium'] ?? 0,
                'ph' => $data['ph'] ?? 0,
                'humidity' => $data['humidity'] ?? 0,
                'soil_type' => $data['type'] ?? 'Unknown',
                'organic_matter' => $data['organic_matter'] ?? 0,
                'api_source' => 'live', // Indique que c'est des données réelles
            ];
        } catch (\Exception $e) {
            $this->logger->error('Erreur appel AgroAPI: ' . $e->getMessage() . " pour location: $location");
            return $this->getTestSoilData($location);
        }
    }

    /**
     * Retourner des données de test quand l'API n'est pas disponible
     */
    private function getTestSoilData(string $location): array
    {
        $location = strtolower(trim($location));

        // Générer un hash déterministe basé sur la localisation pour la variation
        $locationHash = crc32($location);
        $variationSeed = $locationHash % 1000; // Nombre entre 0-999

        // Profils de sol par région tunisienne
        $regionalProfiles = [
            // Régions côtières nord
            'nord_coastal' => [
                'nitrogen' => [20, 35],
                'phosphorus' => [12, 25],
                'potassium' => [100, 180],
                'ph' => [7.0, 7.8],      // Alcalin près de la mer
                'humidity' => [30, 50],   // Sec à modéré
                'soil_types' => ['Sandy Loam', 'Coastal Sand', 'Saline Soil'],
                'organic_matter' => [1.2, 2.8],
                'description' => 'Sol côtier sableux, souvent alcalin'
            ],

            // Régions du nord intérieur (plaines fertiles)
            'nord_interior' => [
                'nitrogen' => [25, 45],
                'phosphorus' => [15, 30],
                'potassium' => [120, 200],
                'ph' => [6.5, 7.5],      // Neutre à légèrement alcalin
                'humidity' => [40, 65],   // Modéré à humide
                'soil_types' => ['Loam', 'Clay Loam', 'Alluvial Soil'],
                'organic_matter' => [2.0, 4.5],
                'description' => 'Sols fertiles des plaines, riches en matière organique'
            ],

            // Régions du centre (steppe)
            'centre_steppe' => [
                'nitrogen' => [15, 30],
                'phosphorus' => [8, 20],
                'potassium' => [80, 150],
                'ph' => [6.8, 8.2],      // Alcalin
                'humidity' => [25, 45],   // Sec
                'soil_types' => ['Sandy Clay', 'Calcareous Soil', 'Arid Soil'],
                'organic_matter' => [0.8, 2.2],
                'description' => 'Sols arides de steppe, souvent calcaires'
            ],

            // Régions du sud (désertique)
            'sud_desert' => [
                'nitrogen' => [10, 25],
                'phosphorus' => [5, 15],
                'potassium' => [60, 120],
                'ph' => [7.5, 8.5],      // Très alcalin
                'humidity' => [15, 35],   // Très sec
                'soil_types' => ['Sandy Desert', 'Saline Desert', 'Gypsum Soil'],
                'organic_matter' => [0.3, 1.5],
                'description' => 'Sols désertiques très pauvres et salins'
            ],

            // Régions urbaines
            'urban' => [
                'nitrogen' => [30, 60],
                'phosphorus' => [20, 40],
                'potassium' => [100, 250],
                'ph' => [6.0, 7.5],      // Variable selon pollution
                'humidity' => [35, 55],   // Modéré
                'soil_types' => ['Urban Soil', 'Contaminated Soil', 'Modified Loam'],
                'organic_matter' => [1.5, 3.5],
                'description' => 'Sols urbains modifiés, souvent enrichis'
            ],

            // Défaut pour localisations inconnues
            'default' => [
                'nitrogen' => [18, 35],
                'phosphorus' => [10, 22],
                'potassium' => [90, 160],
                'ph' => [6.2, 7.3],
                'humidity' => [35, 55],
                'soil_types' => ['Loam', 'Sandy Loam', 'Clay Loam'],
                'organic_matter' => [1.8, 3.2],
                'description' => 'Sol agricole standard'
            ]
        ];

        // Déterminer la région basée sur la localisation
        $region = $this->determineRegion($location);
        $profile = $regionalProfiles[$region] ?? $regionalProfiles['default'];

        // Générer des valeurs dans les plages définies, influencées par le hash
        $nitrogen = $profile['nitrogen'][0] + ($variationSeed % ($profile['nitrogen'][1] - $profile['nitrogen'][0]));
        $phosphorus = $profile['phosphorus'][0] + (($variationSeed * 7) % ($profile['phosphorus'][1] - $profile['phosphorus'][0]));
        $potassium = $profile['potassium'][0] + (($variationSeed * 13) % ($profile['potassium'][1] - $profile['potassium'][0]));

        // pH avec décimales
        $phRange = $profile['ph'][1] - $profile['ph'][0];
        $ph = $profile['ph'][0] + (($variationSeed % 100) / 100) * $phRange;

        // Humidité
        $humidity = $profile['humidity'][0] + (($variationSeed * 3) % ($profile['humidity'][1] - $profile['humidity'][0]));

        // Matière organique
        $omRange = $profile['organic_matter'][1] - $profile['organic_matter'][0];
        $organicMatter = $profile['organic_matter'][0] + (($variationSeed % 100) / 100) * $omRange;

        // Sélectionner un type de sol basé sur le hash
        $soilTypeIndex = $variationSeed % count($profile['soil_types']);
        $soilType = $profile['soil_types'][$soilTypeIndex];

        return [
            'nitrogen' => round($nitrogen, 1),
            'phosphorus' => round($phosphorus, 1),
            'potassium' => round($potassium, 1),
            'ph' => round($ph, 2),
            'humidity' => round($humidity, 1),
            'soil_type' => $soilType,
            'organic_matter' => round($organicMatter, 2),
            'api_source' => 'test',
            'region_profile' => $region,
            'description' => $profile['description']
        ];
    }

    /**
     * Déterminer la région basée sur la localisation
     */
    private function determineRegion(string $location): string
    {
        // Villes côtières nord
        $coastalNorth = ['bizerte', 'tunis', 'ariana', 'ben arous', 'manouba', 'nabeul', 'zaghouan', 'beja', 'jendouba', 'le kef', 'siliana', 'mhamdia', 'kelibia', 'korba', 'tabarka'];

        // Villes centre
        $centre = ['sousse', 'monastir', 'mahdia', 'sfax', 'kairouan', 'kasserine', 'sid bouzid', 'gafsa', 'tozeur', 'kebili', 'medenine', 'tataouine', 'medenine'];

        // Villes sud désertiques
        $sudDesert = ['douze', 'nebri', 'el hamma', 'tameghza', 'borj el khadra', 'remada', 'el faouar'];

        // Termes urbains
        $urbanTerms = ['ville', 'centre', 'cite', 'quartier', 'rue'];

        // Vérifier les correspondances
        foreach ($coastalNorth as $city) {
            if (strpos($location, $city) !== false) {
                return 'nord_coastal';
            }
        }

        foreach ($centre as $city) {
            if (strpos($location, $city) !== false) {
                return 'centre_steppe';
            }
        }

        foreach ($sudDesert as $city) {
            if (strpos($location, $city) !== false) {
                return 'sud_desert';
            }
        }

        foreach ($urbanTerms as $term) {
            if (strpos($location, $term) !== false) {
                return 'urban';
            }
        }

        // Pour les régions intérieures du nord (non côtières)
        if (strpos($location, 'beja') !== false || strpos($location, 'jendouba') !== false ||
            strpos($location, 'le kef') !== false || strpos($location, 'siliana') !== false) {
            return 'nord_interior';
        }

        // Défaut basé sur des indices géographiques
        if (strpos($location, 'sud') !== false || strpos($location, 'desert') !== false) {
            return 'sud_desert';
        }

        if (strpos($location, 'nord') !== false || strpos($location, 'montagne') !== false) {
            return 'nord_interior';
        }

        // Par défaut, région centre (la plus commune)
        return 'centre_steppe';
    }

    /**
     * Calculer qualité du sol (score 0-100)
     */
    private function calculateSoilQuality(array $soilData): int
    {
        $score = 0;

        // Évaluer pH (optimal 6-7 pour la plupart des cultures)
        $ph = $soilData['ph'] ?? 0;
        if ($ph >= 6 && $ph <= 7) {
            $score += 25;
        } elseif ($ph >= 5.5 && $ph <= 7.5) {
            $score += 15;
        } else {
            $score += 5;
        }

        // Évaluer équilibre NPK
        $nitrogen = $soilData['nitrogen'] ?? 0;
        $phosphorus = $soilData['phosphorus'] ?? 0;
        $potassium = $soilData['potassium'] ?? 0;

        $npkAverage = ($nitrogen + $phosphorus + $potassium) / 3;
        if ($npkAverage > 50) {
            $score += 25;
        } elseif ($npkAverage > 30) {
            $score += 15;
        } else {
            $score += 5;
        }

        // Évaluer humidité (optimal 40-60%)
        $humidity = $soilData['humidity'] ?? 0;
        if ($humidity >= 40 && $humidity <= 60) {
            $score += 25;
        } elseif ($humidity >= 30 && $humidity <= 70) {
            $score += 15;
        } else {
            $score += 5;
        }

        // Évaluer matière organique
        $organicMatter = $soilData['organic_matter'] ?? 0;
        if ($organicMatter > 5) {
            $score += 25;
        } elseif ($organicMatter > 2) {
            $score += 15;
        } else {
            $score += 5;
        }

        return min(100, $score);
    }

    /**
     * Générer recommandations de fertilisation
     */
    private function generateRecommendations(array $soilData, Recolte $recolte): array
    {
        $recommendations = [];

        $nitrogen = $soilData['nitrogen'] ?? 0;
        $phosphorus = $soilData['phosphorus'] ?? 0;
        $potassium = $soilData['potassium'] ?? 0;
        $ph = $soilData['ph'] ?? 0;

        // Recommandations Azote
        if ($nitrogen < 30) {
            $recommendations['nitrogen'] = [
                'status' => 'DEFICIENT',
                'level' => $nitrogen,
                'recommended_action' => 'Ajouter engrais azoté - apport 50-100 kg/ha',
                'priority' => 'HIGH'
            ];
        } elseif ($nitrogen > 60) {
            $recommendations['nitrogen'] = [
                'status' => 'EXCESS',
                'level' => $nitrogen,
                'recommended_action' => 'Réduire apports azotés - risque de pollution',
                'priority' => 'MEDIUM'
            ];
        } else {
            $recommendations['nitrogen'] = [
                'status' => 'OPTIMAL',
                'level' => $nitrogen,
                'recommended_action' => 'Maintenir les apports actuels',
                'priority' => 'LOW'
            ];
        }

        // Recommandations Phosphore
        if ($phosphorus < 20) {
            $recommendations['phosphorus'] = [
                'status' => 'DEFICIENT',
                'level' => $phosphorus,
                'recommended_action' => 'Ajouter phosphate - apport 30-50 kg/ha',
                'priority' => 'HIGH'
            ];
        }

        // Recommandations Potassium
        if ($potassium < 150) {
            $recommendations['potassium'] = [
                'status' => 'DEFICIENT',
                'level' => $potassium,
                'recommended_action' => 'Ajouter potasse - apport 50-100 kg/ha',
                'priority' => 'HIGH'
            ];
        }

        // Recommandations pH
        if ($ph < 6) {
            $recommendations['ph'] = [
                'status' => 'TOO_ACIDIC',
                'level' => $ph,
                'recommended_action' => 'Ajouter chaux - 2-5 tonnes/ha selon acidité',
                'priority' => 'HIGH'
            ];
        } elseif ($ph > 7.5) {
            $recommendations['ph'] = [
                'status' => 'TOO_ALKALINE',
                'level' => $ph,
                'recommended_action' => 'Ajouter soufre - améliorer acidité progressivement',
                'priority' => 'MEDIUM'
            ];
        }

        return $recommendations;
    }

    /**
     * Analyser compatibilité du sol avec la récolte
     */
    private function analyzeCompatibilityWithHarvest(array $soilData, Recolte $recolte): array
    {
        $soilType = $soilData['soil_type'] ?? '';
        $cropType = strtolower($recolte->getType_culture());

        $compatibility = [];

        // Définir exigences par culture
        $cropRequirements = [
            'blé' => ['soil_types' => ['loamy', 'clay'], 'ph_min' => 6, 'ph_max' => 7.5],
            'maïs' => ['soil_types' => ['loamy'], 'ph_min' => 5.5, 'ph_max' => 7.5],
            'tomate' => ['soil_types' => ['sandy_loam', 'loamy'], 'ph_min' => 6.2, 'ph_max' => 6.8],
            'pomme_de_terre' => ['soil_types' => ['sandy_loam'], 'ph_min' => 5.2, 'ph_max' => 7],
        ];

        $requirements = $cropRequirements[str_replace(' ', '_', $cropType)] ?? [];

        if (empty($requirements)) {
            return ['status' => 'UNKNOWN', 'message' => 'Type de culture non reconnu'];
        }

        $pH = $soilData['ph'] ?? 0;

        $compatibility['soil_type_match'] = in_array($soilType, $requirements['soil_types'] ?? [])
            ? 'GOOD'
            : 'FAIR';

        $compatibility['pH_match'] = ($pH >= $requirements['ph_min'] && $pH <= $requirements['ph_max'])
            ? 'OPTIMAL'
            : 'SUBOPTIMAL';

        $compatibility['overall_suitability'] = $this->calculateSuitability($compatibility);

        return $compatibility;
    }

    /**
     * Calculer convenance globale du sol
     */
    private function calculateSuitability(array $compatibility): string
    {
        $good_count = count(array_filter($compatibility, fn($v) => in_array($v, ['GOOD', 'OPTIMAL'])));
        $total = count($compatibility) - 1; // Exclure 'overall_suitability'

        if ($good_count === $total) {
            return 'EXCELLENT';
        } elseif ($good_count >= $total / 2) {
            return 'GOOD';
        } else {
            return 'POOR';
        }
    }

    /**
     * Identifier facteurs limitants
     */
    private function identifyLimitingFactors(array $soilAnalysis): array
    {
        $factors = [];

        foreach ($soilAnalysis['recommendations'] ?? [] as $nutrient => $data) {
            if ($data['status'] === 'DEFICIENT') {
                $factors[] = [
                    'nutrient' => ucfirst($nutrient),
                    'level' => $data['level'],
                    'status' => 'DEFICIENT',
                    'impact' => 'LIMITING'
                ];
            }
        }

        return $factors;
    }

    /**
     * Recommander améliorations
     */
    private function recommendImprovements(array $soilAnalysis, float $currentProductivity): array
    {
        $improvements = [];

        $deficiencies = array_filter(
            $soilAnalysis['recommendations'] ?? [],
            fn($v) => $v['status'] === 'DEFICIENT'
        );

        foreach ($deficiencies as $nutrient => $data) {
            $improvements[] = [
                'action' => $data['recommended_action'],
                'expected_yield_increase' => '10-15%',
                'cost_estimate' => 'À évaluer selon intrants',
                'roi_estimate' => 'Élevé (court terme)'
            ];
        }

        return $improvements;
    }

    /**
     * Prédire potentiel de rendement basé sur sol
     */
    private function predictYieldPotential(array $soilAnalysis): float
    {
        $soilQuality = $soilAnalysis['soil_quality'] ?? 0;

        // Formule simple: rendement potentiel proportionnel à qualité du sol
        // Base 100% avec sol optimal (score 100) = 5 kg/m²
        $baseYield = 5.0;
        $qualityFactor = $soilQuality / 100;

        return $baseYield * $qualityFactor;
    }

    /**
     * Calculer corrélation sol/rendement
     */
    private function calculateCorrelation(int $soilQuality, float $productivity): string
    {
        $expectedProductivity = ($soilQuality / 100) * 5;
        $ratio = $productivity / ($expectedProductivity ?: 1);

        if ($ratio > 1.1) {
            return 'POSITIVE - Rendement supérieur aux attentes du sol';
        } elseif ($ratio < 0.9) {
            return 'NEGATIVE - Rendement inférieur au potentiel du sol';
        } else {
            return 'ALIGNED - Rendement conforme au potentiel du sol';
        }
    }
}

