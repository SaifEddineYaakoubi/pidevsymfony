# 💱 Analyse Multi-Devises des Ventes

## Date : 22 avril 2026

---

## 📋 Vue d'ensemble

Cette fonctionnalité permet d'afficher toutes les ventes avec leur équivalent en **EUR (Euro)** et **USD (Dollar Américain)**, avec les taux de change actuels.

---

## ✨ Fonctionnalités

### 1. Conversion Multi-Devises
- Conversion automatique TND → EUR
- Conversion automatique TND → USD
- Affichage des taux de change actuels
- Calcul des totaux dans les 3 devises

### 2. Interface Professionnelle
- Tableau responsive Bootstrap
- Badges colorés pour les montants
- Statistiques en cartes
- Animations d'entrée
- Design moderne et élégant

### 3. Informations Complètes
- ID de la vente
- Client
- Produit et quantité
- Date de vente
- Montants en TND, EUR, USD
- Totaux calculés automatiquement

---

## 🚀 Utilisation

### Accès à l'Analyse

1. **Depuis la liste des ventes** :
   ```
   http://127.0.0.1:8000/vente
   ```

2. **Cliquez sur le bouton** :
   ```
   [💱 Analyse Devises] NEW
   ```

3. **Vous arrivez sur** :
   ```
   http://127.0.0.1:8000/vente/analyse-devises
   ```

---

## 📊 Interface

### En-tête
```
┌─────────────────────────────────────────────────────┐
│ 💱 Analyse des Devises                             │
│ Conversion automatique TND → EUR → USD             │
│                                    [← Retour]       │
└─────────────────────────────────────────────────────┘
```

### Alerte Taux de Change
```
┌─────────────────────────────────────────────────────┐
│ ℹ️ Taux de Change Actuels                          │
│                                                     │
│ 1 TND = [0.3015 EUR] [0.3350 USD]                 │
│ Dernière mise à jour : 22/04/2026 10:30           │
└─────────────────────────────────────────────────────┘
```

### Cartes Statistiques
```
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│ Total TND    │  │ Total EUR    │  │ Total USD    │
│ 1,500.00 DT  │  │ 452.25 €     │  │ 502.50 $     │
└──────────────┘  └──────────────┘  └──────────────┘
```

### Tableau des Ventes
```
┌────┬─────────┬──────────┬──────────┬─────────┬─────────┬─────────┐
│ ID │ Client  │ Produit  │ Date     │ TND     │ EUR     │ USD     │
├────┼─────────┼──────────┼──────────┼─────────┼─────────┼─────────┤
│ #1 │ Ahmed   │ Tomate   │ 20/04/26 │ 150 DT  │ 45.23 € │ 50.25 $ │
│ #2 │ Youssef │ Carotte  │ 21/04/26 │ 200 DT  │ 60.30 € │ 67.00 $ │
│ #3 │ Fatma   │ Oignon   │ 22/04/26 │ 100 DT  │ 30.15 € │ 33.50 $ │
├────┴─────────┴──────────┴──────────┼─────────┼─────────┼─────────┤
│                          TOTAUX :  │ 450 DT  │ 135.68€ │ 150.75$ │
└────────────────────────────────────┴─────────┴─────────┴─────────┘
```

---

## 🔧 Structure Technique

### Service : `CurrencyService`

#### Nouvelles Méthodes

```php
// Convertir TND → USD
public function convertTNDtoUSD(float $montantTND): array

// Récupérer tous les taux (EUR + USD)
public function getAllRates(): array

// Convertir vers plusieurs devises
public function convertTNDtoMultiple(float $montantTND): array
```

#### Retour de `getAllRates()`

```php
[
    'success' => true,
    'taux' => [
        'EUR' => 0.3015,
        'USD' => 0.3350
    ],
    'date_maj' => 'Mon, 22 Apr 2024 00:00:01 +0000',
    'error' => null
]
```

#### Retour de `convertTNDtoMultiple()`

```php
[
    'success' => true,
    'montant_tnd' => 100,
    'conversions' => [
        'EUR' => [
            'montant' => 30.15,
            'taux' => 0.3015
        ],
        'USD' => [
            'montant' => 33.50,
            'taux' => 0.3350
        ]
    ],
    'date_maj' => 'Mon, 22 Apr 2024 00:00:01 +0000',
    'error' => null
]
```

### Controller : `VenteController`

#### Nouvelle Route

```php
#[Route('/vente/analyse-devises', name: 'app_vente_analyse_devises')]
public function analyseDevises(
    VenteRepository $venteRepository,
    CurrencyService $currencyService
): Response
```

**Fonctionnement** :
1. Récupère toutes les ventes de l'utilisateur
2. Récupère les taux de change EUR et USD
3. Calcule les conversions pour chaque vente
4. Calcule les totaux
5. Envoie les données à la vue

### Template : `analyse_devises.html.twig`

**Sections** :
- En-tête avec titre et bouton retour
- Alerte avec taux de change actuels
- 3 cartes statistiques (totaux TND, EUR, USD)
- Tableau responsive avec toutes les ventes
- Pied de page avec totaux
- Informations complémentaires

---

## 🎨 Design

### Couleurs

- **TND** : Bleu primaire (#667eea)
- **EUR** : Bleu foncé (#4f46e5)
- **USD** : Vert (#10b981)

### Badges

```html
<!-- Badge EUR -->
<span class="badge bg-primary">45.23 €</span>

<!-- Badge USD -->
<span class="badge bg-success">50.25 $</span>
```

### Cartes Gradient

```css
/* Carte TND */
background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);

/* Carte EUR */
background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);

/* Carte USD */
background: linear-gradient(135deg, #10b981 0%, #059669 100%);
```

---

## 📱 Responsive

Le tableau est entièrement responsive grâce à Bootstrap :

### Desktop (> 992px)
- Tableau complet avec toutes les colonnes
- Cartes statistiques sur une ligne

### Tablet (768px - 992px)
- Tableau avec scroll horizontal
- Cartes statistiques sur 2 lignes

### Mobile (< 768px)
- Tableau avec scroll horizontal
- Cartes statistiques empilées
- Boutons adaptés

---

## 🧪 Tests

### Test 1 : Affichage Normal

```bash
1. Allez sur : http://127.0.0.1:8000/vente
2. Cliquez sur "Analyse Devises"
3. Vérifiez que le tableau s'affiche
4. Vérifiez les taux de change
5. Vérifiez les totaux
```

**Résultat attendu** :
- Tableau avec toutes les ventes
- Montants en TND, EUR, USD
- Totaux corrects
- Taux de change affichés

### Test 2 : Gestion d'Erreur API

```bash
1. Déconnectez internet
2. Allez sur l'analyse des devises
3. Vérifiez le message d'erreur
4. Vérifiez que les montants TND s'affichent
```

**Résultat attendu** :
- Alerte d'avertissement
- Montants TND visibles
- EUR et USD affichent "N/A"

### Test 3 : Responsive

```bash
1. Ouvrez l'analyse des devises
2. Redimensionnez la fenêtre
3. Testez sur mobile (F12 → mode mobile)
```

**Résultat attendu** :
- Tableau scrollable horizontalement
- Cartes empilées sur mobile
- Tout reste lisible

---

## 💡 Cas d'Usage

### Cas 1 : Rapport International

```
Vous devez envoyer un rapport à un partenaire européen :
1. Allez sur l'analyse des devises
2. Exportez ou copiez les montants EUR
3. Incluez dans votre rapport
```

### Cas 2 : Suivi des Taux

```
Vous voulez suivre l'évolution des taux :
1. Notez les taux actuels
2. Revenez régulièrement
3. Comparez l'évolution
```

### Cas 3 : Décision d'Export

```
Vous envisagez d'exporter :
1. Consultez vos revenus en EUR/USD
2. Évaluez la rentabilité
3. Prenez une décision éclairée
```

---

## 🔍 API ExchangeRate

### Endpoint Utilisé

```
GET https://v6.exchangerate-api.com/v6/{apiKey}/latest/TND
```

### Réponse

```json
{
    "result": "success",
    "base_code": "TND",
    "conversion_rates": {
        "EUR": 0.3015,
        "USD": 0.3350,
        "GBP": 0.2650,
        ...
    },
    "time_last_update_utc": "Mon, 22 Apr 2024 00:00:01 +0000"
}
```

### Limites

- **Plan Gratuit** : 1,500 requêtes/mois
- **Mise à jour** : Toutes les 24 heures
- **Timeout** : 5 secondes

---

## 🚀 Améliorations Futures

### Phase 2

1. **Export Excel/PDF**
   - Bouton d'export
   - Format professionnel
   - Logo et en-tête

2. **Graphiques**
   - Évolution des taux
   - Comparaison mensuelle
   - Tendances

3. **Filtres**
   - Par période
   - Par client
   - Par produit

4. **Plus de Devises**
   - GBP (Livre Sterling)
   - CHF (Franc Suisse)
   - CAD (Dollar Canadien)

5. **Historique des Taux**
   - Stocker en base de données
   - Graphiques d'évolution
   - Comparaison périodes

6. **Alertes**
   - Notification si taux favorable
   - Email automatique
   - Seuils personnalisables

---

## 📊 Impact Business

### Avantages

✅ **Visibilité Internationale** : Voir vos revenus en devises étrangères  
✅ **Décisions Éclairées** : Évaluer la rentabilité d'exports  
✅ **Rapports Professionnels** : Données prêtes pour partenaires  
✅ **Suivi des Taux** : Comprendre l'impact des fluctuations  
✅ **Gain de Temps** : Conversions automatiques  

### Utilisateurs Cibles

- Agriculteurs exportateurs
- Entreprises avec clients internationaux
- Comptables et gestionnaires
- Analystes financiers

---

## 📁 Fichiers Créés/Modifiés

### Créés (1 fichier)
- ✅ `templates/vente/analyse_devises.html.twig`

### Modifiés (3 fichiers)
- ✅ `src/Service/CurrencyService.php` (4 nouvelles méthodes)
- ✅ `src/Controller/VenteController.php` (nouvelle route)
- ✅ `templates/vente/index.html.twig` (bouton ajouté)

---

## 🔗 Liens Rapides

- **Liste des ventes** : `http://127.0.0.1:8000/vente`
- **Analyse devises** : `http://127.0.0.1:8000/vente/analyse-devises`
- **API ExchangeRate** : https://www.exchangerate-api.com/

---

## ✨ Résumé

**Fonctionnalité complète implémentée !** 🎉

- ✅ Service étendu (EUR + USD)
- ✅ Nouvelle route d'analyse
- ✅ Template professionnel
- ✅ Bouton dans la liste
- ✅ Responsive et élégant
- ✅ Gestion des erreurs
- ✅ Animations fluides

**Prêt à l'emploi !** 🚀

---

**Créé le** : 22 avril 2026  
**Version** : 1.0  
**Statut** : ✅ PRODUCTION READY  
**Projet** : SmartFarm - Gestion Agricole Intelligente
