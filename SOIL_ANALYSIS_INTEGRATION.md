# Intégration AgroAPI - Configuration

## Installation

### 1. Installer le bundle HTTP Client (si pas déjà installé)
```bash
composer require symfony/http-client
```

### 2. Configuration du Service

Ajouter dans `config/services.yaml` :

```yaml
services:
    App\Service\SoilAnalysisService:
        arguments:
            - '@http_client'
            - '@logger'
            - '@Symfony\Contracts\Cache\CacheInterface'
            - '%env(AGRO_API_KEY)%'
```

### 3. Variables d'Environnement

Ajouter dans `.env` :

```env
# AgroAPI Configuration
AGRO_API_KEY=your_api_key_here

# Pour un premier test, on peut utiliser une clé test
# AGRO_API_KEY=test_key_free_tier
```

### 4. Service Cache (optionnel mais recommandé)

Dans `config/services.yaml`, s'assurer que le cache est configuré :

```yaml
framework:
    cache:
        default: cache.app
        pools:
            cache.app: ~
```

## Routes Disponibles

### 1. Analyser Sol pour une Récolte
```
GET /soil/recolte/{id_recolte}
```
Affiche l'analyse complète du sol pour une récolte spécifique.

**Exemple:**
```
http://localhost:8000/soil/recolte/1
```

### 2. Analyser Impact Sol sur Rendement
```
GET /soil/rendement/{idRendement}
```
Affiche l'impact du sol sur le rendement obtenu et recommandations.

**Exemple:**
```
http://localhost:8000/soil/rendement/1
```

### 3. API REST - Analyse Complète Rendement
```
GET /soil/api/rendement/{idRendement}/analysis
```
Retourne JSON avec analyse complète.

**Réponse Example:**
```json
{
    "soil_quality_score": 75,
    "productivity": 3.5,
    "correlation": "POSITIVE - Rendement supérieur aux attentes du sol",
    "soil_factors_affecting_yield": [
        {
            "nutrient": "Nitrogen",
            "level": 35,
            "status": "DEFICIENT",
            "impact": "LIMITING"
        }
    ],
    "recommendations_to_improve": [
        {
            "action": "Ajouter engrais azoté - apport 50-100 kg/ha",
            "expected_yield_increase": "10-15%",
            "cost_estimate": "À évaluer selon intrants",
            "roi_estimate": "Élevé (court terme)"
        }
    ],
    "predicted_yield_potential": 4.2,
    "actual_vs_potential": {
        "actual": 3.5,
        "potential": 4.2,
        "efficiency": "83.33%"
    }
}
```

## Données Fournies par AgroAPI

### 1. NPK (Azote, Phosphore, Potassium)
- **Azote (N)**: Mesure en ppm (parts per million)
  - Déficient: < 30 ppm
  - Optimal: 30-60 ppm
  - Excès: > 60 ppm

- **Phosphore (P)**: Mesure en ppm
  - Déficient: < 20 ppm
  - Optimal: 20-40 ppm

- **Potassium (K)**: Mesure en ppm
  - Déficient: < 150 ppm
  - Optimal: 150-300 ppm

### 2. pH du Sol
- Acidique: < 6
- Optimal: 6-7
- Alcalin: > 7.5

### 3. Humidité
- Faible: < 30%
- Optimal: 40-60%
- Élevée: > 70%

### 4. Type de Sol
- Sandy (Sableux)
- Loamy (Limoneux)
- Clay (Argileux)
- Sandy Loam
- Silt Loam

## Cas d'Usage

### Cas 1: Analyse d'une Récolte
```
1. Agriculteur ajoute une récolte
2. Va dans "Analyse du Sol" pour cette récolte
3. Voit les données NPK, pH, humidité
4. Reçoit recommandations de fertilisation
5. Planifie les apports pour la prochaine récolte
```

### Cas 2: Comprendre un Rendement Faible
```
1. Agriculteur voit que le rendement est faible (2 kg/m²)
2. Va dans "Impact du Sol sur Rendement"
3. Découvre que l'azote est déficient
4. Applique recommendations pour prochaine récolte
5. Monitore l'impact
```

### Cas 3: Optimiser la Production
```
1. Agriculteur a efficacité de 60%
2. Identifie facteurs limitants via l'analyse
3. Reçoit plan d'amélioration avec ROI
4. Applique recommandations
5. Prévoit amélioration de 10-15% du rendement
```

## Intégration avec Recolte et Rendement

### Au niveau Recolte
- Afficher analyse du sol lors de la consultation d'une récolte
- Bouton "Voir analyse du sol" sur détail récolte
- Recommandations basées sur type culture

### Au niveau Rendement
- Lier performance rendement à qualité du sol
- Identifier causes de faibles rendements
- Prédire potentiel futur basé sur sol

### N'affecte PAS
- Entité Culture (non modifiée)
- Entité Parcelle (non modifiée)
- Workflows existants

## Caching

Les appels API sont cachés 24h pour:
- Améliorer performance
- Réduire coûts API
- Éviter appels répétés

Pour invalider le cache:
```bash
php bin/console cache:clear
```

## Sécurité

- Seul l'agriculteur propriétaire de la récolte peut voir l'analyse
- Vérification utilisateur à chaque requête
- API Key stockée en variable d'environnement

## Troubleshooting

### Erreur "Impossible de récupérer les données du sol"
- Vérifier AGRO_API_KEY dans .env
- Vérifier la connexion internet
- Voir les logs: `tail -f var/log/dev.log`

### Performance lente
- Caching est activé (24h)
- Vérifier la base de données
- Vérifier la latence de l'API AgroAPI

### Données manquantes
- Vérifier que la localisation de la récolte est correctement renseignée
- Vérifier la couverture de l'API pour cette région

## Prochaines Étapes

1. **Notifications**: Alerter si nutrient déficient
2. **Historique**: Tracker amélioration au fil du temps
3. **Export PDF**: Rapport complet d'analyse
4. **Prédictions ML**: Machine Learning pour meilleures prédictions
5. **Intégration Météo**: Corréler avec conditions climatiques

