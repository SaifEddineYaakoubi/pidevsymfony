# 📍 Système de Géolocalisation et Frais de Livraison Automatiques

## 🎯 Vue d'ensemble

Ce système détecte automatiquement la localisation du client lors de la création d'une vente et calcule les frais de livraison en fonction de sa région.

## 🔑 Configuration

### 1. Clé API ipgeolocation.io

**Clé API actuelle**: `a4e0f0f96e534b798307c75c99535908`

La clé est configurée dans `.env.local`:
```env
IPGEOLOCATION_KEY=a4e0f0f96e534b798307c75c99535908
```

### 2. Configuration du service

Le service est configuré dans `config/services.yaml`:
```yaml
parameters:
    ipgeolocation_api_key: '%env(IPGEOLOCATION_KEY)%'

services:
    App\Service\GeoLocationService:
        arguments:
            $apiKey: '%ipgeolocation_api_key%'
```

## 📊 Structure de la base de données

### Nouveaux champs dans l'entité `Vente`

Trois nouveaux champs ont été ajoutés:

```php
#[ORM\Column(type: "string", length: 100, nullable: true)]
private ?string $ville = null;

#[ORM\Column(type: "string", length: 100, nullable: true)]
private ?string $region = null;

#[ORM\Column(type: "float", nullable: true)]
private ?float $frais_livraison = null;
```

**Migration exécutée**: `doctrine:schema:update --force` (4 queries)

## 🛠️ Service GeoLocationService

### Méthodes disponibles

#### 1. `getLocation(string $ip): array`

Récupère la ville et la région à partir d'une adresse IP.

**Paramètres**:
- `$ip` (string): Adresse IP du visiteur

**Retour**:
```php
[
    'success' => true,
    'city' => 'Tunis',
    'region' => 'Tunis Governorate',
    'country' => 'Tunisia',
    'latitude' => 36.8065,
    'longitude' => 10.1815,
    'error' => null
]
```

**Gestion IP locale**:
- Si l'IP est `127.0.0.1`, `::1` ou `localhost`, elle est automatiquement convertie en `197.0.0.1` (IP tunisienne) pour les tests.

#### 2. `calculateShippingCost(string $region): float`

Calcule les frais de livraison en fonction de la région.

**Règles**:
- Si la région contient "Tunis": **7 DT**
- Autres régions: **12 DT**

**Exemple**:
```php
$frais = $geoLocationService->calculateShippingCost('Tunis Governorate'); // 7.0
$frais = $geoLocationService->calculateShippingCost('Sfax Governorate');  // 12.0
```

#### 3. `getLocationWithShipping(string $ip): array`

Récupère la localisation ET calcule les frais de livraison en une seule opération.

**Retour**:
```php
[
    'success' => true,
    'city' => 'Tunis',
    'region' => 'Tunis Governorate',
    'country' => 'Tunisia',
    'frais_livraison' => 7.0,
    'error' => null
]
```

**En cas d'erreur**:
```php
[
    'success' => false,
    'city' => null,
    'region' => null,
    'country' => null,
    'frais_livraison' => 12.0, // Frais par défaut
    'error' => 'Impossible de contacter le service de géolocalisation'
]
```

## 🎮 Utilisation dans VenteController

### Méthode `new()` (Front Office)

Lors de la création d'une vente, le système:

1. **Récupère l'IP du visiteur**:
```php
$clientIp = $request->getClientIp();
```

2. **Obtient la localisation et les frais**:
```php
$locationData = $geoLocationService->getLocationWithShipping($clientIp);
```

3. **Enregistre les données dans la vente**:
```php
$vente->setVille($locationData['city']);
$vente->setRegion($locationData['region']);
$vente->setFraisLivraison($locationData['frais_livraison']);
```

4. **Affiche un message flash**:
```php
if ($locationData['success']) {
    $this->addFlash('info', sprintf(
        '📍 Livraison vers %s, %s - Frais: %s DT',
        $locationData['city'],
        $locationData['region'],
        $locationData['frais_livraison']
    ));
} else {
    $this->addFlash('warning', sprintf(
        '⚠️ Géolocalisation indisponible. Frais par défaut: %s DT',
        $locationData['frais_livraison']
    ));
}
```

## 🧪 Tests et débogage

### Test avec IP locale

Lorsque vous testez en local (localhost), l'IP `127.0.0.1` est automatiquement convertie en `197.0.0.1` (IP tunisienne).

### Logs

Le service utilise le logger Symfony pour tracer:
- Les appels API
- Les réponses reçues
- Les erreurs réseau
- Les conversions d'IP locale

**Exemple de logs**:
```
[info] IP locale détectée, utilisation de 197.0.0.1 pour les tests
[info] Appel API Geolocation {"ip":"197.0.0.1","url":"https://api.ipgeolocation.io/ipgeo"}
[info] Réponse API Geolocation {"city":"Tunis","region":"Tunis Governorate","country":"Tunisia"}
```

### Vérifier les données en base

Après avoir créé une vente, vérifiez que les champs sont bien remplis:

```sql
SELECT id_vente, ville, region, frais_livraison, montant_total 
FROM vente 
ORDER BY id_vente DESC 
LIMIT 5;
```

## 🔒 Gestion des erreurs

Le service gère plusieurs types d'erreurs:

### 1. Erreur réseau (timeout, connexion impossible)
```php
[
    'success' => false,
    'error' => 'Impossible de contacter le service de géolocalisation',
    'frais_livraison' => 12.0 // Frais par défaut
]
```

### 2. Erreur HTTP (4xx, 5xx)
```php
[
    'success' => false,
    'error' => 'Erreur du service de géolocalisation',
    'frais_livraison' => 12.0
]
```

### 3. Données incomplètes
```php
[
    'success' => false,
    'error' => 'Données de localisation incomplètes',
    'frais_livraison' => 12.0
]
```

**Important**: En cas d'erreur, l'application continue de fonctionner avec des frais par défaut de **12 DT**.

## 📈 Évolutions possibles

### 1. Ajouter plus de régions
Modifier la méthode `calculateShippingCost()`:
```php
public function calculateShippingCost(string $region): float
{
    if (stripos($region, 'Tunis') !== false) {
        return 7.0;
    } elseif (stripos($region, 'Sfax') !== false) {
        return 9.0;
    } elseif (stripos($region, 'Sousse') !== false) {
        return 8.0;
    }
    
    return 12.0; // Autres régions
}
```

### 2. Afficher la localisation dans les templates

Dans `templates/vente/show.html.twig`:
```twig
{% if vente.ville and vente.region %}
<div class="alert alert-info">
    <i class="bi bi-geo-alt-fill"></i>
    <strong>Livraison:</strong> {{ vente.ville }}, {{ vente.region }}
    <span class="badge bg-primary">{{ vente.fraisLivraison }} DT</span>
</div>
{% endif %}
```

### 3. Statistiques par région

Créer une méthode dans `VenteRepository`:
```php
public function getVentesByRegion(): array
{
    return $this->createQueryBuilder('v')
        ->select('v.region, COUNT(v.id_vente) as total_ventes, SUM(v.montant_total) as total_montant')
        ->where('v.region IS NOT NULL')
        ->groupBy('v.region')
        ->orderBy('total_ventes', 'DESC')
        ->getQuery()
        ->getResult();
}
```

### 4. Cache des localisations

Pour éviter d'appeler l'API à chaque fois, vous pouvez mettre en cache les résultats:
```php
use Symfony\Contracts\Cache\CacheInterface;

public function getLocationWithCache(string $ip, CacheInterface $cache): array
{
    $cacheKey = 'geolocation_' . str_replace('.', '_', $ip);
    
    return $cache->get($cacheKey, function() use ($ip) {
        return $this->getLocation($ip);
    });
}
```

## 🎓 Exemple complet d'utilisation

```php
// Dans un contrôleur
public function createVente(
    Request $request,
    GeoLocationService $geoLocationService,
    EntityManagerInterface $em
): Response {
    $vente = new Vente();
    
    // Récupérer l'IP du client
    $clientIp = $request->getClientIp();
    
    // Obtenir la localisation et les frais
    $locationData = $geoLocationService->getLocationWithShipping($clientIp);
    
    // Remplir la vente
    $vente->setVille($locationData['city']);
    $vente->setRegion($locationData['region']);
    $vente->setFraisLivraison($locationData['frais_livraison']);
    
    // Sauvegarder
    $em->persist($vente);
    $em->flush();
    
    // Message de confirmation
    if ($locationData['success']) {
        $this->addFlash('success', sprintf(
            'Vente créée! Livraison vers %s - Frais: %s DT',
            $locationData['city'],
            $locationData['frais_livraison']
        ));
    }
    
    return $this->redirectToRoute('app_vente_index');
}
```

## 📞 Support

- **API Documentation**: https://ipgeolocation.io/documentation.html
- **Limite gratuite**: 1000 requêtes/jour
- **Timeout configuré**: 5 secondes

## ✅ Checklist de vérification

- [x] Clé API configurée dans `.env.local`
- [x] Service configuré dans `services.yaml`
- [x] Champs ajoutés à l'entité `Vente`
- [x] Migration exécutée
- [x] Méthode `new()` du contrôleur modifiée
- [x] Gestion des erreurs implémentée
- [x] Logs configurés
- [x] Conversion IP locale pour les tests
- [x] Messages flash pour l'utilisateur

---

**Date de création**: 22 avril 2026  
**Version**: 1.0  
**Auteur**: Système de géolocalisation automatique
