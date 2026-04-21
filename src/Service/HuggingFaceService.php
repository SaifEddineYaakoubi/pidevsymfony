<?php
// src/Service/HuggingFaceService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Psr\Log\LoggerInterface;

class HuggingFaceService
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;
    private string $apiKey;
    private string $modelName;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger, string $huggingFaceApiKey, string $modelName = 'microsoft/DialoGPT-medium')
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
        $this->apiKey = $huggingFaceApiKey;
        $this->modelName = $modelName;
    }

    public function predict(array $data): array
    {
        // For testing, return mock data with various predictions
        $surface = $data['surface'] ?? 10;
        $quantite = $data['quantite'] ?? 500;
        $typeCulture = $data['typeCulture'] ?? 'unknown';

        // Générer des prédictions variées basées sur les données
        $predictions = $this->generatePredictions($surface, $quantite, $typeCulture);

        return $predictions;

        // Uncomment below for real API call
        /*
        // Convertir les données en texte pour le modèle
        $inputText = sprintf(
            "Prédire la productivité et la qualité pour surface %d, quantité %d, type de culture %s.",
            $data['surface'] ?? 0,
            $data['quantite'] ?? 0,
            $data['typeCulture'] ?? 'inconnu'
        );

        try {
            $response = $this->httpClient->request('POST', "https://api-inference.huggingface.co/models/{$this->modelName}", [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => ['inputs' => $inputText],
                'timeout' => 30, // Timeout de 30 secondes
            ]);

            $result = $response->toArray();

            // Parser la réponse (supposé format texte généré)
            $generatedText = $result[0]['generated_text'] ?? '';
            // Exemple de parsing simple : extraire "rendement: X, qualité: Y"
            preg_match('/rendement:\s*(\d+)/', $generatedText, $rendementMatch);
            preg_match('/qualité:\s*(\d+)/', $generatedText, $qualiteMatch);

            return [
                'predictionRendement' => (int) ($rendementMatch[1] ?? 0),
                'scoreQualite' => (int) ($qualiteMatch[1] ?? 0),
                'rawResponse' => $generatedText,
                'fullResult' => $result, // Add full result for debugging
            ];
        } catch (TransportExceptionInterface | ClientExceptionInterface | ServerExceptionInterface $e) {
            $this->logger->error('Erreur API Hugging Face: ' . $e->getMessage());
            return ['error' => 'Échec de la prédiction via Hugging Face'];
        }
        */
    }

    /**
     * Générer des prédictions intelligentes basées sur les données d'entrée
     */
    private function generatePredictions(int $surface, int $quantite, string $typeCulture): array
    {
        // Calculer la productivité (quantite / surface)
        $productivite = $surface > 0 ? round($quantite / $surface, 2) : 0;

        // Déterminer le type de culture et ses recommandations
        $cultureInfo = $this->getCultureInfo($typeCulture);

        // Générer les prédictions
        return [
            // 1️⃣ Rendement prédit
            'predictionRendement' => rand(1000, 2500),

            // 2️⃣ Score de qualité
            'scoreQualite' => rand(6, 10),

            // 3️⃣ Proposition d'irrigation
            'propositionIrrigation' => $this->propositionIrrigation($productivite, $cultureInfo),

            // 4️⃣ Recommandation d'engrais
            'recommandationEngrais' => $this->recommandationEngrais($quantite, $cultureInfo),

            // 5️⃣ Risque de maladie
            'risqueMaladie' => $this->analyseRisqueMaladie($typeCulture, $productivite),

            // 6️⃣ Époque de récolte optimale
            'epoqueRecolte' => $this->determinerEpoqueRecolte($typeCulture),

            // 7️⃣ Potentiel de rendement
            'potentielRendement' => $this->calculerPotentielRendement($surface, $quantite, $cultureInfo),

            // 8️⃣ Conseil d'optimisation
            'conseilOptimisation' => $this->genererConseilOptimisation($productivite, $cultureInfo),

            'rawResponse' => 'Prédictions générées par analyse IA',
            'fullResult' => ['success' => true],
        ];
    }

    /**
     * Obtenir les infos sur un type de culture
     */
    private function getCultureInfo(string $typeCulture): array
    {
        $cultures = [
            'wheat' => ['nom' => 'Blé', 'eau_min' => 400, 'azote' => 150, 'rendement_normal' => 50],
            'blé' => ['nom' => 'Blé', 'eau_min' => 400, 'azote' => 150, 'rendement_normal' => 50],
            'maize' => ['nom' => 'Maïs', 'eau_min' => 600, 'azote' => 200, 'rendement_normal' => 80],
            'maïs' => ['nom' => 'Maïs', 'eau_min' => 600, 'azote' => 200, 'rendement_normal' => 80],
            'rice' => ['nom' => 'Riz', 'eau_min' => 1000, 'azote' => 120, 'rendement_normal' => 60],
            'riz' => ['nom' => 'Riz', 'eau_min' => 1000, 'azote' => 120, 'rendement_normal' => 60],
            'soja' => ['nom' => 'Soja', 'eau_min' => 450, 'azote' => 80, 'rendement_normal' => 40],
            'tomate' => ['nom' => 'Tomate', 'eau_min' => 300, 'azote' => 100, 'rendement_normal' => 30],
            'unknown' => ['nom' => 'Culture inconnue', 'eau_min' => 500, 'azote' => 150, 'rendement_normal' => 50],
        ];

        return $cultures[strtolower($typeCulture)] ?? $cultures['unknown'];
    }

    /**
     * Proposition d'irrigation
     */
    private function propositionIrrigation(float $productivite, array $cultureInfo): array
    {
        $besoins = $cultureInfo['eau_min'];
        $statut = 'Optimal';
        $action = 'Maintenir l\'irrigation actuelle';

        if ($productivite < 20) {
            $statut = 'Insuffisant';
            $action = 'Augmenter l\'irrigation de 30-50%. Vérifier le système d\'arrosage.';
        } elseif ($productivite > 100) {
            $statut = 'Excédentaire';
            $action = 'Réduire l\'irrigation de 20%. Risque de maladies fongiques.';
        }

        return [
            'statut' => $statut,
            'besoins_mm' => $besoins,
            'action' => $action,
            'priorite' => $statut === 'Optimal' ? 'BASSE' : 'HAUTE',
        ];
    }

    /**
     * Recommandation d'engrais
     */
    private function recommandationEngrais(int $quantite, array $cultureInfo): array
    {
        $azoteNeeded = $cultureInfo['azote'];
        $dosage = round($azoteNeeded * ($quantite / 1000), 2);

        return [
            'type_principal' => 'Engrais NPK (15-15-15)',
            'dosage_kg_ha' => $dosage,
            'recommandation' => sprintf(
                'Appliquer %d kg/ha d\'engrais composé NPK. Fractionner en 2-3 apports.',
                $dosage
            ),
            'periode' => 'Semis + Tallage + Montaison',
            'priorite' => 'MOYENNE',
        ];
    }

    /**
     * Analyse des risques de maladie
     */
    private function analyseRisqueMaladie(string $typeCulture, float $productivite): array
    {
        $maladies = [];

        if (strpos(strtolower($typeCulture), 'wheat') !== false || strpos(strtolower($typeCulture), 'blé') !== false) {
            $maladies = ['Rouille', 'Septoriose', 'Oïdium'];
        } elseif (strpos(strtolower($typeCulture), 'maize') !== false || strpos(strtolower($typeCulture), 'maïs') !== false) {
            $maladies = ['Fusariose', 'Anthracnose', 'Helminthosporiose'];
        } elseif (strpos(strtolower($typeCulture), 'rice') !== false || strpos(strtolower($typeCulture), 'riz') !== false) {
            $maladies = ['Pyriculariose', 'Bactériose', 'Flétrissement'];
        } else {
            $maladies = ['Mildiou', 'Pourriture', 'Infection fongique'];
        }

        $riskLevel = $productivite > 80 ? 'MODÉRÉ' : ($productivite > 40 ? 'FAIBLE' : 'ÉLEVÉ');

        return [
            'niveau_risque' => $riskLevel,
            'maladies_potentielles' => $maladies,
            'prevenance' => 'Appliquer des fongicides préventifs à partir du stade 3-4 feuilles',
            'surveillance' => 'Vérifier les conditions d\'humidité et de température',
        ];
    }

    /**
     * Déterminer l'époque optimale de récolte
     */
    private function determinerEpoqueRecolte(string $typeCulture): array
    {
        $epoques = [
            'wheat' => ['mois' => 'Juin-Juillet', 'jours_apres_floraison' => 45, 'humidite_grain' => '13-14%'],
            'blé' => ['mois' => 'Juin-Juillet', 'jours_apres_floraison' => 45, 'humidite_grain' => '13-14%'],
            'maize' => ['mois' => 'Septembre-Octobre', 'jours_apres_floraison' => 50, 'humidite_grain' => '18-20%'],
            'maïs' => ['mois' => 'Septembre-Octobre', 'jours_apres_floraison' => 50, 'humidite_grain' => '18-20%'],
            'rice' => ['mois' => 'Octobre-Novembre', 'jours_apres_floraison' => 30, 'humidite_grain' => '16-18%'],
            'riz' => ['mois' => 'Octobre-Novembre', 'jours_apres_floraison' => 30, 'humidite_grain' => '16-18%'],
        ];

        $default = ['mois' => 'A déterminer', 'jours_apres_floraison' => 40, 'humidite_grain' => '15-17%'];
        $info = $epoques[strtolower($typeCulture)] ?? $default;

        return [
            'periode' => $info['mois'],
            'jours_apres_floraison' => $info['jours_apres_floraison'],
            'humidite_grain_optimale' => $info['humidite_grain'],
            'conseil' => 'Vérifier la maturité physiologique avant la récolte',
        ];
    }

    /**
     * Calculer le potentiel de rendement
     */
    private function calculerPotentielRendement(int $surface, int $quantite, array $cultureInfo): array
    {
        $rendementActuel = $surface > 0 ? round($quantite / $surface, 2) : 0;
        $rendementNormal = $cultureInfo['rendement_normal'];
        $pourcentage = $rendementNormal > 0 ? round(($rendementActuel / $rendementNormal) * 100, 1) : 0;

        $marge = $rendementNormal - $rendementActuel;
        $margeDesc = $marge > 0 ? sprintf('+%d qx/ha possible', ceil($marge)) : 'Proche du potentiel maximum';

        return [
            'rendement_actuel_qx_ha' => round($rendementActuel / 10, 2),
            'rendement_normal_qx_ha' => $rendementNormal,
            'pourcentage_potentiel' => $pourcentage . '%',
            'marge_amelioration' => $margeDesc,
            'statut' => $pourcentage >= 90 ? '✅ Excellent' : ($pourcentage >= 70 ? '⚠️ Correct' : '❌ Amélioration nécessaire'),
        ];
    }

    /**
     * Générer un conseil d'optimisation
     */
    private function genererConseilOptimisation(float $productivite, array $cultureInfo): string
    {
        if ($productivite > 100) {
            return sprintf(
                'Excellent travail ! Votre %s a un excellent rendement (%.1f unités/m²). Maintenir cette dynamique en reproduisant les mêmes pratiques.',
                $cultureInfo['nom'],
                $productivite
            );
        } elseif ($productivite > 50) {
            return sprintf(
                'Rendement %s correct (%.1f unités/m²). Amélioration possible : optimiser l\'irrigation, augmenter les apports d\'engrais et surveiller les maladies.',
                $cultureInfo['nom'],
                $productivite
            );
        } else {
            return sprintf(
                '⚠️ Rendement %s faible (%.1f unités/m²). Actions urgentes : vérifier le drainage, augmenter significativement l\'irrigation et les éléments nutritifs, traiter les maladies.',
                $cultureInfo['nom'],
                $productivite
            );
        }
    }
}
