# 🧪 Guide Complet - Tester l'API AgroAPI

## 🚀 Étape 1: Installation des Dépendances

### Installer le HTTP Client Symfony
```bash
cd C:\Users\admin\Desktop\pidevsymfony

composer require symfony/http-client

# Puis clear le cache
php bin/console cache:clear
```

---

## ⚙️ Étape 2: Configuration

### 1. Créer/Modifier le fichier `.env`

Ajouter les variables:
```env
# Dans C:\Users\admin\Desktop\pidevsymfony\.env

# AgroAPI Configuration
AGRO_API_KEY=test_key_free_tier
# Ou obtenir une vraie clé depuis https://agroapi.com
```

### 2. Configurer le Service dans `config/services.yaml`

```yaml
services:
    App\Service\SoilAnalysisService:
        arguments:
            - '@http_client'
            - '@logger'
            - '@cache.app'
            - '%env(AGRO_API_KEY)%'
```

### 3. Vérifier que les fichiers sont en place

Les fichiers suivants doivent exister:
- `src/Service/SoilAnalysisService.php` ✓
- `src/Controller/SoilAnalysisController.php` ✓
- `templates/soil_analysis/recolte.html.twig` ✓
- `templates/soil_analysis/rendement.html.twig` ✓

```bash
# Vérifier depuis la racine du projet
dir src\Service\SoilAnalysisService.php
dir src\Controller\SoilAnalysisController.php
dir templates\soil_analysis\
```

---

## 🔧 Étape 3: Tester avec Postman/Insomnia

### 1. Démarrer le serveur Symfony

```bash
cd C:\Users\admin\Desktop\pidevsymfony

# Option 1: Serveur intégré
php -S 127.0.0.1:8000 -t public/

# Option 2: Symfony CLI (si installé)
symfony server:start
```

L'application sera accessible sur: `http://localhost:8000`

### 2. Tester les Routes via le Navigateur

#### Test 1: Analyser une Récolte
```
URL: http://localhost:8000/soil/recolte/1
Méthode: GET
Authentification: Vous devez être connecté en tant qu'agriculteur
```

**Étapes:**
1. Se connecter à l'application (`http://localhost:8000`)
2. Créer une récolte (ou utiliser une existante avec ID 1)
3. Accéder à: `http://localhost:8000/soil/recolte/1`

**Résultat attendu:**
- Page avec analyse du sol
- Score qualité: 78/100
- Données NPK
- pH, humidité
- Recommandations

#### Test 2: Analyser Impact sur Rendement
```
URL: http://localhost:8000/soil/rendement/1
Méthode: GET
Authentification: Requis
```

**Étapes:**
1. Créer/avoir un rendement avec ID 1
2. Accéder à: `http://localhost:8000/soil/rendement/1`

**Résultat attendu:**
- Productivité réelle vs potentielle
- Facteurs limitants
- Recommandations amélioration

#### Test 3: API REST (JSON)
```
URL: http://localhost:8000/soil/api/rendement/1/analysis
Méthode: GET
Accept: application/json
```

**Résultat attendu (JSON):**
```json
{
    "soil_quality_score": 78,
    "productivity": 3.5,
    "correlation": "POSITIVE - Rendement supérieur aux attentes du sol",
    "soil_factors_affecting_yield": [],
    "recommendations_to_improve": [],
    "predicted_yield_potential": 4.2,
    "actual_vs_potential": {
        "actual": 3.5,
        "potential": 4.2,
        "efficiency": "83.33%"
    }
}
```

---

## 🧪 Étape 4: Tester avec PHP CLI

### 1. Créer un Script de Test

Créer le fichier: `bin/test-soil-analysis.php`

```php
<?php
// bin/test-soil-analysis.php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

// Créer un client HTTP
$client = HttpClient::create();

try {
    // Test 1: Appel simple API (sans authentification)
    echo "=== Test 1: Appel API Direct ===\n";
    
    $response = $client->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
        'query' => [
            'q' => 'Mareth',
            'appid' => 'test_key',
            'units' => 'metric',
        ]
    ]);
    
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Headers: " . json_encode($response->getHeaders(), JSON_PRETTY_PRINT) . "\n";
    
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}

echo "\n✓ Test CLI terminé\n";
```

### 2. Exécuter le Test

```bash
cd C:\Users\admin\Desktop\pidevsymfony

php bin/test-soil-analysis.php
```

---

## 🧬 Étape 5: Tester via le Navigateur avec Affichage de Debug

### 1. Modifier le Service pour Afficher des Logs

Éditer `src/Service/SoilAnalysisService.php` et ajouter:

```php
// Dans getSoilAnalysisForRecolte()

private function fetchSoilDataFromAPI(string $location): array
{
    $this->logger->info('🌍 Appel API AgroAPI', [
        'location' => $location,
        'api_key' => substr($this->apiKey, 0, 5) . '...' // Masquer la clé
    ]);
    
    try {
        // ... code existant ...
        
        $this->logger->info('✓ Données reçues', [
            'nitrogen' => $data['npk']['nitrogen'] ?? 'N/A',
            'phosphorus' => $data['npk']['phosphorus'] ?? 'N/A',
            'potassium' => $data['npk']['potassium'] ?? 'N/A',
        ]);
        
    } catch (\Exception $e) {
        $this->logger->error('❌ Erreur API', [
            'message' => $e->getMessage(),
            'code' => $e->getCode()
        ]);
    }
}
```

### 2. Vérifier les Logs

```bash
# Afficher les logs en temps réel
tail -f C:\Users\admin\Desktop\pidevsymfony\var\log\dev.log

# Ou ouvrir le fichier
type C:\Users\admin\Desktop\pidevsymfony\var\log\dev.log
```

---

## 📋 Étape 6: Tester les Données Mock (Sans Vraie API)

### 1. Créer une Version de Test

Créer: `src/Service/SoilAnalysisServiceMock.php`

```php
<?php

namespace App\Service;

use App\Entity\Recolte;
use App\Entity\Rendement;

/**
 * Version Mock - Retourne des données de test
 * Utile pour tester sans avoir une vraie clé API
 */
class SoilAnalysisServiceMock
{
    public function getSoilAnalysisForRecolte(Recolte $recolte): array
    {
        return [
            'npk' => [
                'nitrogen' => 45,
                'phosphorus' => 25,
                'potassium' => 200,
            ],
            'ph' => 6.5,
            'humidity' => 55,
            'soil_type' => 'Loamy',
            'soil_quality' => 78,
            'recommendations' => [
                'nitrogen' => [
                    'status' => 'OPTIMAL',
                    'level' => 45,
                    'recommended_action' => 'Maintenir les apports actuels',
                    'priority' => 'LOW'
                ],
                'phosphorus' => [
                    'status' => 'OPTIMAL',
                    'level' => 25,
                    'recommended_action' => 'Maintenir les apports actuels',
                    'priority' => 'LOW'
                ],
            ],
            'harvest_compatibility' => [
                'soil_type_match' => 'GOOD',
                'pH_match' => 'OPTIMAL',
                'overall_suitability' => 'EXCELLENT'
            ]
        ];
    }

    public function analyzeImpactOnYield(Rendement $rendement): array
    {
        return [
            'soil_quality_score' => 78,
            'productivity' => 3.5,
            'correlation' => 'POSITIVE - Rendement supérieur aux attentes du sol',
            'soil_factors_affecting_yield' => [],
            'recommendations_to_improve' => [
                [
                    'action' => 'Maintenir fertilisation actuelle',
                    'expected_yield_increase' => 'Déjà optimal',
                    'cost_estimate' => 'Minimal',
                    'roi_estimate' => 'Excellent'
                ]
            ],
            'predicted_yield_potential' => 4.2,
            'actual_vs_potential' => [
                'actual' => 3.5,
                'potential' => 4.2,
                'efficiency' => '83.33%'
            ]
        ];
    }
}
```

### 2. Utiliser le Mock dans le Contrôleur (Temporaire)

```php
// Dans SoilAnalysisController.php - pour tester

public function analyzeRecolte(
    int $id_recolte,
    RecolteRepository $recolteRepository
): Response {
    // ... validation code ...
    
    // Utiliser le mock pour tester
    $soilAnalysis = [
        'npk' => ['nitrogen' => 45, 'phosphorus' => 25, 'potassium' => 200],
        'ph' => 6.5,
        'humidity' => 55,
        'soil_type' => 'Loamy',
        'soil_quality' => 78,
        'recommendations' => []
    ];
    
    return $this->render('soil_analysis/recolte.html.twig', [
        'recolte' => $recolte,
        'soil_analysis' => $soilAnalysis,
    ]);
}
```

---

## ✅ Checklist de Test Complète

### Test 1: Configuration ✓
- [ ] HTTP Client installé (`composer require symfony/http-client`)
- [ ] `.env` contient `AGRO_API_KEY`
- [ ] `config/services.yaml` configuré
- [ ] Cache clear exécuté (`php bin/console cache:clear`)

### Test 2: Routes Disponibles ✓
- [ ] Route `/soil/recolte/{id}` accessible
- [ ] Route `/soil/rendement/{id}` accessible
- [ ] Route `/soil/api/rendement/{id}/analysis` retourne JSON

### Test 3: Authentification ✓
- [ ] Utilisateur doit être connecté
- [ ] Seules les données de l'utilisateur sont visibles
- [ ] Accès refusé pour autre utilisateur

### Test 4: Affichage des Données ✓
- [ ] Score qualité du sol affiché
- [ ] NPK détaillé visible
- [ ] Recommandations présentes
- [ ] Compatibilité culture affichée

### Test 5: Performance ✓
- [ ] Première requête: rapide (appel API)
- [ ] Deuxième requête: instantanée (cache)
- [ ] Cache expire après 24h

---

## 🐛 Troubleshooting

### Problème 1: Page Blanche
**Solution:**
```bash
# Clear le cache
php bin/console cache:clear

# Vérifier les logs
type var\log\dev.log
```

### Problème 2: Erreur 404 (Route Non Trouvée)
**Solution:**
```bash
# Vérifier que le contrôleur est correctement créé
dir src\Controller\SoilAnalysisController.php

# Lister les routes
php bin/console debug:router | findstr soil
```

### Problème 3: Erreur API (API Key Invalide)
**Solution:**
```env
# Dans .env, utiliser une clé valide
AGRO_API_KEY=your_real_api_key_here

# Ou utiliser le mock pour développement
```

### Problème 4: Erreur CORS (Si API externe)
**Solution:**
```php
// Dans SoilAnalysisService.php
$response = $this->httpClient->request('GET', $url, [
    'headers' => [
        'Accept' => 'application/json',
        'User-Agent' => 'Symfony AgroApp/1.0'
    ]
]);
```

---

## 📊 Scénarios de Test Complets

### Scénario 1: Test Complet d'une Récolte
```
1. Se connecter: http://localhost:8000/login
2. Créer récolte:
   - Type: "Blé"
   - Localisation: "Mareth, Sfax"
   - Date: "2026-04-10"
   - Quantité: "1000 kg"
   - Qualité: "Bonne"
3. Accéder à: http://localhost:8000/recolte/1
4. Cliquer: "Analyser le sol"
5. Vérifier:
   - ✓ Score qualité visible
   - ✓ NPK affiché
   - ✓ Recommandations présentes
```

### Scénario 2: Test Impact Rendement
```
1. Créer rendement pour récolte
   - Productivité: "3.5 kg/m²"
   - Surface: "100 m²"
2. Accéder: http://localhost:8000/soil/rendement/1
3. Vérifier:
   - ✓ Productivité réelle affichée
   - ✓ Potentiel calculé
   - ✓ Efficacité en %
   - ✓ Recommandations d'amélioration
```

### Scénario 3: Test API REST
```bash
# Utiliser curl pour tester l'API
curl -X GET "http://localhost:8000/soil/api/rendement/1/analysis" ^
     -H "Accept: application/json" ^
     -H "Cookie: PHPSESSID=your_session_id"

# Vérifier le JSON retourné
# Doit contenir: soil_quality_score, productivity, correlation, etc.
```

---

## 🚀 Commandes Utiles

```bash
# Afficher les routes Soil
php bin/console debug:router | findstr soil

# Vérifier le service est chargé
php bin/console debug:container | findstr SoilAnalysis

# Tester cache
php bin/console cache:clear
php bin/console cache:warmup

# Vérifier configuration services.yaml
php bin/console config:dump-reference services

# Voir les logs en temps réel
Get-Content -Path var\log\dev.log -Wait

# Ou créer un test unitaire
php bin/console make:test SoilAnalysisServiceTest
```

---

## ✨ Résumé

Pour tester l'intégration AgroAPI:

1. **Installation**: `composer require symfony/http-client`
2. **Configuration**: Ajouter `AGRO_API_KEY` dans `.env`
3. **Serveur**: `php -S 127.0.0.1:8000 -t public/`
4. **Test Navigation**: Accéder à `/soil/recolte/1`
5. **Test API**: GET `/soil/api/rendement/1/analysis`
6. **Vérifier**: Logs dans `var/log/dev.log`

Bonne chance avec les tests! 🧪

