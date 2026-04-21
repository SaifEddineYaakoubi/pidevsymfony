# 🤖 Outil d'Analyse IA - Documentation Complète

## 🎯 Ce qui a été fait

J'ai créé un **outil d'analyse IA** qui analyse automatiquement les visiteurs (utilisateurs) de votre plateforme et fournit :

### 📊 Statistiques calculées

1. **Moyenne d'âge** des utilisateurs
2. **Âge médian**
3. **Tranche d'âge** (min-max)
4. **Répartition par sexe** (homme/femme/autre/non spécifié)
5. **Répartition par tranche d'âge** (0-17, 18-24, 25-34, 35-44, 45-54, 55-64, 65+)

### 🧠 Insights IA (Recommandations intelligentes)

L'IA génère automatiquement des recommandations basées sur les données :
- Analyse de l'audience (jeune/mature/senior)
- Parité homme/femme
- Tranche d'âge dominante
- Suggestions d'actions

### 📈 Prédictions

- Tendance de croissance
- Tendance démographique
- Genre dominant

### 📉 Visualisations

- **Graphique en donut** : Répartition par sexe
- **Graphique en barres** : Répartition par tranche d'âge
- **Cartes statistiques** : Chiffres clés
- **Cartes de prédictions** : Tendances futures

---

## 🛠️ Fichiers créés/modifiés

### Backend (PHP/Symfony)

#### 1. Entité Utilisateur
**Fichier** : `src/Entity/Utilisateur.php`

**Colonnes ajoutées** :
```php
#[ORM\Column(type: 'date', nullable: true)]
private ?\DateTimeInterface $dateNaissance = null;

#[ORM\Column(type: 'string', length: 10, nullable: true)]
private ?string $sexe = null;
```

**Méthodes ajoutées** :
```php
public function getDateNaissance(): ?\DateTimeInterface
public function setDateNaissance(?\DateTimeInterface $dateNaissance): self
public function getSexe(): ?string
public function setSexe(?string $sexe): self
public function getAge(): ?int  // Calcule l'âge automatiquement
```

#### 2. Contrôleur AIAnalyticsController
**Fichier** : `src/Controller/AIAnalyticsController.php`

**Routes** :
- `GET /admin/ai-analytics` - Page principale
- `GET /admin/ai-analytics/data` - API JSON avec les statistiques

**Méthodes** :
- `index()` - Affiche la page
- `getData()` - Retourne les statistiques en JSON
- `calculateStatistics()` - Calcule toutes les statistiques
- `generatePredictions()` - Génère les prédictions IA
- `generateInsights()` - Génère les recommandations intelligentes

### Frontend (Twig/JavaScript)

#### 3. Vue principale
**Fichier** : `templates/admin/ai_analytics/index.html.twig`

**Composants** :
- Loader animé pendant le chargement
- 4 cartes de statistiques principales
- 2 graphiques (Chart.js)
- Section insights IA
- Section prédictions

**Technologies** :
- Chart.js 3.9.1 (graphiques)
- jQuery (AJAX)
- AdminLTE (design)

#### 4. Sidebar
**Fichier** : `templates/admin/partials/_sidebar.html.twig`

**Ajout** :
- Lien "Analyse IA" avec badge "NEW"
- Icône cerveau (fa-brain)

### Base de données

**Colonnes ajoutées à `utilisateur`** :
```sql
date_naissance DATE NULL
sexe VARCHAR(10) NULL
```

### Documentation

**Fichiers créés** :
- `OUTIL_IA_ANALYTICS.md` - Ce fichier
- `DONNEES_TEST_IA.sql` - Script pour ajouter des données de test

---

## 📊 Comment ça fonctionne

### Flux de données

```
1. Utilisateur accède à /admin/ai-analytics
   ↓
2. Page affiche un loader
   ↓
3. Requête AJAX vers /admin/ai-analytics/data
   ↓
4. Contrôleur récupère tous les utilisateurs
   ↓
5. Calcul des statistiques :
   - Moyenne d'âge
   - Répartition par sexe
   - Répartition par tranche d'âge
   ↓
6. Génération des insights IA
   ↓
7. Génération des prédictions
   ↓
8. Retour JSON au frontend
   ↓
9. Affichage des graphiques et statistiques
```

### Algorithmes utilisés

#### Calcul de l'âge
```php
public function getAge(): ?int
{
    if (!$this->dateNaissance) {
        return null;
    }
    
    $now = new \DateTime();
    $interval = $this->dateNaissance->diff($now);
    return $interval->y;
}
```

#### Calcul de la moyenne d'âge
```php
$averageAge = count($ages) > 0 ? round(array_sum($ages) / count($ages), 1) : 0;
```

#### Calcul de l'âge médian
```php
sort($ages);
$middle = floor(count($ages) / 2);
if (count($ages) % 2 == 0) {
    $medianAge = ($ages[$middle - 1] + $ages[$middle]) / 2;
} else {
    $medianAge = $ages[$middle];
}
```

#### Répartition par sexe
```php
$sexe = strtolower($user->getSexe() ?? '');
if ($sexe === 'homme' || $sexe === 'h' || $sexe === 'm' || $sexe === 'male') {
    $sexes['homme']++;
} elseif ($sexe === 'femme' || $sexe === 'f' || $sexe === 'female') {
    $sexes['femme']++;
}
```

#### Génération des insights IA
```php
// Exemple : Insight sur l'âge moyen
if ($averageAge < 25) {
    $insights[] = [
        'type' => 'info',
        'icon' => 'fa-users',
        'title' => 'Audience Jeune',
        'message' => "Votre audience est principalement jeune..."
    ];
}
```

---

## 🎨 Interface utilisateur

### Cartes de statistiques

**4 cartes principales** :
1. **Utilisateurs Total** (bleu) - Nombre total d'utilisateurs
2. **Âge Moyen** (vert) - Moyenne d'âge calculée
3. **Âge Médian** (orange) - Âge médian
4. **Tranche d'Âge** (rouge) - Min-Max

### Graphiques

#### 1. Graphique en donut (Répartition par sexe)
- **Type** : Doughnut Chart
- **Couleurs** :
  - Hommes : Bleu (#3498db)
  - Femmes : Rouge (#e74c3c)
  - Autre : Violet (#9b59b6)
  - Non spécifié : Gris (#95a5a6)
- **Affichage** : Nombre + Pourcentage

#### 2. Graphique en barres (Répartition par âge)
- **Type** : Bar Chart
- **Couleur** : Vert (#2ecc71)
- **Tranches** : 0-17, 18-24, 25-34, 35-44, 45-54, 55-64, 65+

### Insights IA

**3 types d'insights** :
1. **Audience** (jeune/mature/senior)
2. **Parité** (équilibrée/dominante)
3. **Tranche dominante**

**Affichage** :
- Cartes colorées (info/success/warning)
- Icônes Font Awesome
- Titre + Message

### Prédictions

**3 prédictions** :
1. **Tendance de croissance** (positive/stable)
2. **Tendance démographique** (jeune/mature/senior)
3. **Genre dominant** (masculin/féminin)

---

## 🚀 Comment utiliser

### 1. Accéder à l'outil

```
1. Connectez-vous en tant qu'administrateur
2. Dans la sidebar, cliquez sur "Analyse IA"
3. La page se charge automatiquement
```

### 2. Ajouter des données de test

**Option A : Script SQL automatique**
```sql
-- Exécutez dans votre base de données
UPDATE utilisateur SET 
    date_naissance = DATE_SUB(CURDATE(), INTERVAL FLOOR(20 + RAND() * 40) YEAR),
    sexe = CASE 
        WHEN RAND() < 0.5 THEN 'homme'
        ELSE 'femme'
    END
WHERE date_naissance IS NULL;
```

**Option B : Manuellement**
```sql
UPDATE utilisateur SET date_naissance = '1990-05-15', sexe = 'homme' WHERE id_user = 1;
UPDATE utilisateur SET date_naissance = '1985-08-22', sexe = 'femme' WHERE id_user = 2;
UPDATE utilisateur SET date_naissance = '1995-03-10', sexe = 'homme' WHERE id_user = 3;
```

**Option C : Via le profil utilisateur**
- Modifiez le formulaire de profil pour ajouter date de naissance et sexe
- Les utilisateurs remplissent eux-mêmes

### 3. Interpréter les résultats

#### Moyenne d'âge
- **< 25 ans** : Audience jeune et dynamique
- **25-40 ans** : Audience mature et professionnelle
- **> 40 ans** : Audience senior et expérimentée

#### Répartition par sexe
- **Équilibrée** (40-60%) : Bonne parité
- **Déséquilibrée** (< 40% ou > 60%) : Dominance d'un genre

#### Insights IA
- Lisez les recommandations
- Adaptez votre stratégie en conséquence

---

## 🔧 Configuration et personnalisation

### Modifier les tranches d'âge

**Fichier** : `src/Controller/AIAnalyticsController.php`

```php
$ageRanges = [
    '0-17' => 0,
    '18-24' => 0,
    '25-34' => 0,
    '35-44' => 0,
    '45-54' => 0,
    '55-64' => 0,
    '65+' => 0
];
```

**Modifier** :
```php
$ageRanges = [
    '0-20' => 0,
    '21-30' => 0,
    '31-40' => 0,
    '41-50' => 0,
    '51+' => 0
];
```

### Modifier les couleurs des graphiques

**Fichier** : `templates/admin/ai_analytics/index.html.twig`

```javascript
const genderColors = {
    'homme': '#3498db',  // Bleu
    'femme': '#e74c3c',  // Rouge
    'autre': '#9b59b6',  // Violet
    'non_specifie': '#95a5a6'  // Gris
};
```

### Ajouter de nouveaux insights

**Fichier** : `src/Controller/AIAnalyticsController.php`

```php
private function generateInsights(...): array
{
    $insights = [];
    
    // Votre nouvel insight
    if ($condition) {
        $insights[] = [
            'type' => 'success',  // info/success/warning/danger
            'icon' => 'fa-star',
            'title' => 'Titre',
            'message' => 'Message'
        ];
    }
    
    return $insights;
}
```

---

## 📈 Exemples de résultats

### Exemple 1 : Startup tech

```
Total utilisateurs : 50
Âge moyen : 28.5 ans
Âge médian : 27 ans
Tranche : 22-45 ans

Répartition par sexe :
- Hommes : 32 (64%)
- Femmes : 18 (36%)

Insights :
✓ Audience jeune et dynamique
⚠ Dominance masculine - Considérez des actions pour diversifier
ℹ Tranche 25-34 ans dominante

Prédictions :
- Croissance : Positive (+12%)
- Démographique : Audience jeune et dynamique
- Genre dominant : Masculin
```

### Exemple 2 : Association senior

```
Total utilisateurs : 30
Âge moyen : 52.3 ans
Âge médian : 54 ans
Tranche : 35-72 ans

Répartition par sexe :
- Hommes : 14 (47%)
- Femmes : 16 (53%)

Insights :
⚠ Audience mature - Misez sur l'expertise
✓ Excellente parité homme/femme
ℹ Tranche 45-54 ans dominante

Prédictions :
- Croissance : Stable
- Démographique : Audience mature et expérimentée
- Genre dominant : Féminin
```

---

## 🐛 Dépannage

### Erreur "Aucune donnée"

**Cause** : Pas d'utilisateurs avec date de naissance

**Solution** :
```sql
UPDATE utilisateur SET 
    date_naissance = '1990-01-01', 
    sexe = 'homme' 
WHERE id_user = 1;
```

### Graphiques ne s'affichent pas

**Cause** : Chart.js non chargé

**Solution** :
1. Vérifiez votre connexion internet
2. Rechargez la page (Ctrl+F5)
3. Vérifiez la console (F12)

### Erreur 403 Forbidden

**Cause** : Pas les droits administrateur

**Solution** :
- Connectez-vous avec un compte admin
- Vérifiez que l'utilisateur a le rôle ROLE_ADMIN

---

## 📊 API JSON

### Endpoint

```
GET /admin/ai-analytics/data
```

### Réponse

```json
{
  "total_users": 50,
  "average_age": 28.5,
  "median_age": 27.0,
  "age_range": {
    "min": 22,
    "max": 45
  },
  "sexe_distribution": {
    "counts": {
      "homme": 32,
      "femme": 18,
      "autre": 0,
      "non_specifie": 0
    },
    "percentages": {
      "homme": 64.0,
      "femme": 36.0,
      "autre": 0.0,
      "non_specifie": 0.0
    }
  },
  "age_ranges": {
    "counts": {
      "0-17": 0,
      "18-24": 12,
      "25-34": 28,
      "35-44": 8,
      "45-54": 2,
      "55-64": 0,
      "65+": 0
    },
    "percentages": {
      "0-17": 0.0,
      "18-24": 24.0,
      "25-34": 56.0,
      "35-44": 16.0,
      "45-54": 4.0,
      "55-64": 0.0,
      "65+": 0.0
    }
  },
  "predictions": {
    "growth_trend": "positive",
    "growth_percentage": 12,
    "demographic_trend": "Audience jeune et dynamique",
    "dominant_gender": "homme"
  },
  "insights": [
    {
      "type": "info",
      "icon": "fa-users",
      "title": "Audience Jeune",
      "message": "Votre audience est principalement jeune..."
    }
  ]
}
```

---

## 🎯 Améliorations possibles

### Court terme

1. **Export PDF/Excel**
   - Exporter les statistiques
   - Générer des rapports

2. **Filtres**
   - Par rôle (admin/stock/agriculteur)
   - Par période d'inscription
   - Par statut (actif/inactif)

3. **Comparaison temporelle**
   - Évolution mois par mois
   - Tendances sur 6 mois/1 an

### Moyen terme

4. **Machine Learning réel**
   - Prédictions plus précises
   - Clustering d'utilisateurs
   - Détection d'anomalies

5. **Tableaux de bord personnalisés**
   - Widgets déplaçables
   - Graphiques personnalisables
   - Alertes automatiques

6. **Intégration externe**
   - Google Analytics
   - Facebook Insights
   - API tierces

### Long terme

7. **IA avancée**
   - Recommandations personnalisées
   - Prédiction de churn
   - Segmentation automatique

8. **Temps réel**
   - Mise à jour en temps réel
   - WebSockets
   - Notifications push

---

## ✅ Résumé

### Ce qui a été fait

✅ **Entité Utilisateur** mise à jour (date_naissance, sexe)
✅ **Contrôleur AIAnalyticsController** créé
✅ **Page d'analyse IA** avec graphiques
✅ **Calcul automatique** des statistiques
✅ **Insights IA** intelligents
✅ **Prédictions** basées sur les données
✅ **Visualisations** (Chart.js)
✅ **Lien dans la sidebar**
✅ **Base de données** synchronisée
✅ **Documentation** complète

### Fichiers créés

- `src/Controller/AIAnalyticsController.php`
- `templates/admin/ai_analytics/index.html.twig`
- `OUTIL_IA_ANALYTICS.md`
- `DONNEES_TEST_IA.sql`

### Fichiers modifiés

- `src/Entity/Utilisateur.php`
- `templates/admin/partials/_sidebar.html.twig`

### Base de données

- Colonne `date_naissance` (DATE)
- Colonne `sexe` (VARCHAR 10)

---

**Votre outil d'analyse IA est prêt !** 🤖

Accédez-y via la sidebar → "Analyse IA" 📊
