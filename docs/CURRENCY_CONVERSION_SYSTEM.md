# 💱 Système de Conversion de Devises TND → EUR

## Date : 22 avril 2026

---

## 📋 Vue d'ensemble

Ce système permet d'afficher le montant total d'une vente en **EUR (Euro)** en temps réel, en plus du montant en **TND (Dinar Tunisien)**, en utilisant l'API ExchangeRate.

---

## ✅ Fonctionnalités

### Conversion en Temps Réel
- Conversion automatique TND → EUR lors de l'affichage d'une vente
- Utilisation de l'API ExchangeRate pour les taux de change actuels
- Affichage du taux de change utilisé
- Gestion des erreurs (API indisponible, timeout, etc.)

### Affichage
- Badge Bootstrap élégant avec le montant en EUR
- Icône de conversion de devise
- Taux de change affiché en petit texte
- Message d'erreur si la conversion échoue

---

## 🔧 Configuration

### 1. Clé API ExchangeRate

**Fichier** : `.env`

```env
### > EXCHANGE RATE API ###
EXCHANGE_RATE_KEY=56d2b2daf39cd76328159a4c
### < EXCHANGE RATE API ###
```

### 2. Configuration Symfony

**Fichier** : `config/services.yaml`

```yaml
parameters:
    exchange_rate_api_key: '%env(EXCHANGE_RATE_KEY)%'

services:
    # Service de conversion de devises
    App\Service\CurrencyService:
        arguments:
            $apiKey: '%exchange_rate_api_key%'
```

---

## 📁 Structure Technique

### Service : `CurrencyService`

**Fichier** : `src/Service/CurrencyService.php`

#### Méthodes Principales

```php
// Convertir un montant TND en EUR
public function convertTNDtoEUR(float $montantTND): array

// Récupérer le taux de change actuel
public function getTauxTNDtoEUR(): array
```

#### Retour de `convertTNDtoEUR()`

```php
[
    'success' => true|false,
    'montant_eur' => float|null,      // Montant converti en EUR
    'taux' => float|null,              // Taux de change utilisé
    'error' => string|null             // Message d'erreur si échec
]
```

### Controller : `VenteController`

**Méthode modifiée** : `show()`

```php
#[Route('/vente/{idVente}', name: 'app_vente_show', methods: ['GET'])]
public function show(
    int $idVente, 
    EntityManagerInterface $entityManager,
    \App\Service\CurrencyService $currencyService
): Response
{
    // ... récupération de la vente ...
    
    // Convertir le montant en EUR
    $montantTND = $vente->getMontantTotal();
    $conversionResult = $currencyService->convertTNDtoEUR($montantTND);

    return $this->render('vente/show.html.twig', [
        'vente' => $vente,
        'conversion' => $conversionResult,
    ]);
}
```

### Template : `show.html.twig`

**Fichier** : `templates/vente/show.html.twig`

```twig
<tr>
    <th>Montant Total</th>
    <td>
        <strong class="text-success">{{ vente.montantTotal|number_format(2, ',', ' ') }} TND</strong>
        
        {# Affichage de la conversion en EUR #}
        {% if conversion.success %}
            <span class="badge bg-primary ms-2" style="font-size: 0.9rem;">
                <i class="bi bi-currency-exchange"></i>
                ≈ {{ conversion.montant_eur|number_format(2, ',', ' ') }} EUR
            </span>
            <br>
            <small class="text-muted">
                Taux de change: 1 TND = {{ conversion.taux|number_format(4, ',', ' ') }} EUR
            </small>
        {% elseif conversion.error %}
            <br>
            <small class="text-warning">
                <i class="bi bi-exclamation-triangle"></i>
                {{ conversion.error }}
            </small>
        {% endif %}
    </td>
</tr>
```

---

## 🚀 Utilisation

### Afficher une Vente avec Conversion

1. Connectez-vous à votre compte
2. Allez sur la liste des ventes : `http://127.0.0.1:8000/vente`
3. Cliquez sur une vente pour voir les détails
4. Le montant en EUR s'affiche automatiquement à côté du montant en TND

### Exemple d'Affichage

```
Montant Total: 150.00 TND  [≈ 45.23 EUR]
Taux de change: 1 TND = 0.3015 EUR
```

---

## 🔒 Gestion des Erreurs

Le service gère automatiquement plusieurs types d'erreurs :

### 1. Erreur Réseau
```
Message : "Impossible de contacter le service de conversion"
Cause : Pas de connexion internet, API inaccessible
```

### 2. Erreur HTTP
```
Message : "Erreur du service de conversion"
Cause : API retourne une erreur 4xx ou 5xx
```

### 3. Timeout
```
Message : "Service de conversion temporairement indisponible"
Cause : L'API met plus de 5 secondes à répondre
```

### 4. Erreur Inattendue
```
Message : "Erreur inattendue lors de la conversion"
Cause : Toute autre erreur non prévue
```

### Affichage en Cas d'Erreur

```twig
Montant Total: 150.00 TND
⚠️ Service de conversion temporairement indisponible
```

**Important** : En cas d'erreur, le site continue de fonctionner normalement. Seule la conversion EUR n'est pas affichée.

---

## 📊 API ExchangeRate

### URL de l'API

```
https://v6.exchangerate-api.com/v6/{apiKey}/pair/TND/EUR/{montant}
```

### Exemple de Requête

```
GET https://v6.exchangerate-api.com/v6/56d2b2daf39cd76328159a4c/pair/TND/EUR/150
```

### Exemple de Réponse

```json
{
    "result": "success",
    "documentation": "https://www.exchangerate-api.com/docs",
    "terms_of_use": "https://www.exchangerate-api.com/terms",
    "time_last_update_unix": 1713744001,
    "time_last_update_utc": "Mon, 22 Apr 2024 00:00:01 +0000",
    "time_next_update_unix": 1713830401,
    "time_next_update_utc": "Tue, 23 Apr 2024 00:00:01 +0000",
    "base_code": "TND",
    "target_code": "EUR",
    "conversion_rate": 0.3015,
    "conversion_result": 45.23
}
```

### Limites de l'API

**Plan Gratuit** :
- 1,500 requêtes par mois
- Mise à jour des taux : toutes les 24 heures
- Timeout : 5 secondes

**Recommandation** : Pour un usage intensif, envisagez un plan payant ou implémentez un système de cache.

---

## 🎨 Personnalisation

### Modifier le Style du Badge

**Fichier** : `templates/vente/show.html.twig`

```twig
{# Badge bleu (par défaut) #}
<span class="badge bg-primary ms-2">

{# Badge vert #}
<span class="badge bg-success ms-2">

{# Badge orange #}
<span class="badge bg-warning ms-2">

{# Badge personnalisé #}
<span class="badge ms-2" style="background-color: #667eea; font-size: 1rem;">
```

### Modifier le Timeout

**Fichier** : `src/Service/CurrencyService.php`

```php
$response = $this->httpClient->request('GET', $url, [
    'timeout' => 5,  // Changez cette valeur (en secondes)
]);
```

### Ajouter d'Autres Devises

**Exemple** : Ajouter USD

```php
// Dans CurrencyService.php
public function convertTNDtoUSD(float $montantTND): array
{
    $url = sprintf(
        '%s/%s/pair/TND/USD/%s',
        self::API_BASE_URL,
        $this->apiKey,
        $montantTND
    );
    
    // ... même logique que convertTNDtoEUR ...
}
```

---

## 🧪 Tests

### Test 1 : Conversion Réussie

```bash
1. Créez une vente avec un montant de 100 TND
2. Allez sur la page de détails de la vente
3. Vérifiez que le montant en EUR s'affiche
4. Vérifiez que le taux de change est affiché
```

**Résultat attendu** :
```
Montant Total: 100.00 TND  [≈ 30.15 EUR]
Taux de change: 1 TND = 0.3015 EUR
```

### Test 2 : Gestion d'Erreur

```bash
1. Déconnectez votre internet
2. Allez sur la page de détails d'une vente
3. Vérifiez qu'un message d'erreur s'affiche
4. Vérifiez que le site fonctionne toujours
```

**Résultat attendu** :
```
Montant Total: 100.00 TND
⚠️ Impossible de contacter le service de conversion
```

### Test 3 : Vérifier les Logs

```bash
# Voir les logs d'erreur
tail -f var/log/dev.log | grep CurrencyService
```

---

## 🔍 Débogage

### Vérifier la Clé API

```bash
# Afficher la valeur de la variable d'environnement
php bin/console debug:container --env-vars | grep EXCHANGE_RATE
```

### Tester l'API Manuellement

```bash
# Avec curl
curl "https://v6.exchangerate-api.com/v6/56d2b2daf39cd76328159a4c/pair/TND/EUR/100"
```

### Vérifier les Logs

```bash
# Logs Symfony
tail -f var/log/dev.log

# Filtrer les erreurs du CurrencyService
grep "CurrencyService" var/log/dev.log
```

---

## 📈 Améliorations Futures

### Phase 2

1. **Cache des Taux de Change**
   - Stocker les taux en cache (Redis/Memcached)
   - Durée : 24 heures (mise à jour quotidienne)
   - Réduire les appels API

2. **Conversion Multiple**
   - Ajouter USD, GBP, CHF
   - Sélecteur de devise dans l'interface
   - Afficher plusieurs devises simultanément

3. **Historique des Taux**
   - Stocker l'historique en base de données
   - Graphiques d'évolution des taux
   - Comparaison sur plusieurs périodes

4. **Conversion dans la Liste**
   - Afficher EUR dans la liste des ventes
   - Colonne supplémentaire "Montant EUR"
   - Tri par montant EUR

5. **Export avec Conversion**
   - PDF avec montants TND et EUR
   - Excel avec colonnes TND et EUR
   - Factures bilingues

---

## 💡 Bonnes Pratiques

### 1. Ne Jamais Bloquer l'Application
✅ Le service gère toutes les erreurs
✅ L'application fonctionne même si l'API est down
✅ Timeout de 5 secondes maximum

### 2. Logger les Erreurs
✅ Toutes les erreurs sont loggées
✅ Facilite le débogage
✅ Permet de surveiller la disponibilité de l'API

### 3. Affichage Clair
✅ Badge visible et élégant
✅ Taux de change affiché
✅ Messages d'erreur explicites

### 4. Sécurité
✅ Clé API dans .env (pas dans le code)
✅ .env dans .gitignore
✅ Validation des données de l'API

---

## 📞 Support

### Documentation API
- **Site officiel** : https://www.exchangerate-api.com/
- **Documentation** : https://www.exchangerate-api.com/docs

### Code Source
- **Service** : `src/Service/CurrencyService.php`
- **Controller** : `src/Controller/VenteController.php` (méthode `show`)
- **Template** : `templates/vente/show.html.twig`
- **Config** : `config/services.yaml`

---

## ✨ Résumé

**Fonctionnalité implémentée avec succès !** 🎉

- ✅ Service de conversion créé
- ✅ Configuration Symfony complète
- ✅ Controller modifié
- ✅ Template mis à jour
- ✅ Gestion des erreurs robuste
- ✅ Logs pour le débogage
- ✅ Documentation complète

**Prêt à l'emploi !** 🚀

---

**Créé le** : 22 avril 2026  
**Version** : 1.0  
**Statut** : ✅ ACTIF  
**Projet** : SmartFarm - Gestion Agricole Intelligente
