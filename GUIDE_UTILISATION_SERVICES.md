# Guide d'Utilisation des Services Métier

## 📚 Table des Matières

1. [CultureManager](#culturemanager)
2. [RecolteManager](#recoltemanager)
3. [Exemples Pratiques](#exemples-pratiques)
4. [Gestion des Erreurs](#gestion-des-erreurs)

---

## CultureManager

### Injection du Service

```php
use App\Service\CultureManager;

class MyCultureController
{
    public function __construct(
        private CultureManager $cultureManager
    ) {
    }
}
```

### Créer une Culture

```php
use App\Entity\Culture;
use App\Entity\Parcelle;

// Créer une culture avec validation automatique
try {
    $culture = $this->cultureManager->createCulture(
        typeCulture: 'Maïs',
        datePlantation: new \DateTime('2024-01-01'),
        dateRecoltePrevue: new \DateTime('2024-06-01'),
        parcelle: $parcelle
    );
    
    // La culture est créée et validée
    $this->entityManager->persist($culture);
    $this->entityManager->flush();
} catch (\InvalidArgumentException $e) {
    // Gérer l'erreur
    echo "Erreur: " . $e->getMessage();
}
```

### Valider une Culture

```php
// Valider une culture existante
$violations = $this->cultureManager->validate($culture);

if ($violations->count() > 0) {
    foreach ($violations as $violation) {
        echo $violation->getMessage();
    }
}
```

### Valider les Dates

```php
// Valider que les dates sont cohérentes
try {
    $this->cultureManager->validateDates(
        datePlantation: new \DateTime('2024-01-01'),
        dateRecoltePrevue: new \DateTime('2024-06-01')
    );
    echo "Dates valides!";
} catch (\InvalidArgumentException $e) {
    echo "Erreur: " . $e->getMessage();
}
```

### Calculer l'État de Croissance

```php
// Calculer automatiquement l'état de croissance
$state = $this->cultureManager->calculateGrowthState($culture);

echo "État actuel: " . $state;
// Résultat: "germination", "croissance", "floraison", ou "maturite"
```

### Vérifier si une Culture est en Retard

```php
// Vérifier si la culture est en retard
if ($this->cultureManager->isDelayed($culture)) {
    echo "⚠️ Cette culture est en retard!";
} else {
    echo "✅ La culture est à jour";
}
```

### Obtenir la Progression

```php
// Obtenir le pourcentage de progression
$progress = $this->cultureManager->getProgressPercentage($culture);

echo "Progression: " . round($progress, 2) . "%";

// Afficher une barre de progression
if ($progress < 25) {
    echo "🟩⬜⬜⬜ Germination";
} elseif ($progress < 60) {
    echo "🟩🟩⬜⬜ Croissance";
} elseif ($progress < 90) {
    echo "🟩🟩🟩⬜ Floraison";
} else {
    echo "🟩🟩🟩🟩 Maturité";
}
```

---

## RecolteManager

### Injection du Service

```php
use App\Service\RecolteManager;

class MyRecolteController
{
    public function __construct(
        private RecolteManager $recolteManager
    ) {
    }
}
```

### Créer une Récolte

```php
use App\Entity\Recolte;
use App\Entity\Utilisateur;

// Créer une récolte avec validation automatique
try {
    $recolte = $this->recolteManager->createRecolte(
        quantite: 150.5,
        dateRecolte: new \DateTime('2024-05-15'),
        qualite: 'bonne',
        typeCulture: 'Maïs',
        localisation: 'Champ Nord',
        utilisateur: $user,
        culture: $culture // optionnel
    );
    
    // La récolte est créée et validée
    $this->entityManager->persist($recolte);
    $this->entityManager->flush();
} catch (\InvalidArgumentException $e) {
    // Gérer l'erreur
    echo "Erreur: " . $e->getMessage();
}
```

### Valider une Récolte

```php
// Valider une récolte existante
$violations = $this->recolteManager->validate($recolte);

if ($violations->count() > 0) {
    foreach ($violations as $violation) {
        echo $violation->getMessage();
    }
}
```

### Valider la Quantité

```php
// Valider que la quantité est positive
try {
    $this->recolteManager->validateQuantite(150.5);
    echo "Quantité valide!";
} catch (\InvalidArgumentException $e) {
    echo "Erreur: " . $e->getMessage();
}
```

### Valider la Date

```php
// Valider que la date n'est pas dans le futur
try {
    $this->recolteManager->validateDateRecolte(new \DateTime('2024-05-15'));
    echo "Date valide!";
} catch (\InvalidArgumentException $e) {
    echo "Erreur: " . $e->getMessage();
}
```

### Valider la Qualité

```php
// Valider que la qualité est l'une des valeurs acceptées
try {
    $this->recolteManager->validateQualite('bonne');
    echo "Qualité valide!";
} catch (\InvalidArgumentException $e) {
    echo "Erreur: " . $e->getMessage();
}
```

### Calculer le Rendement

```php
// Calculer le rendement (quantité / superficie)
$yield = $this->recolteManager->calculateYield($recolte);

echo "Rendement: " . round($yield, 2) . " unités/hectare";
```

### Vérifier la Qualité

```php
// Vérifier si la récolte est de bonne qualité
if ($this->recolteManager->isGoodQuality($recolte)) {
    echo "✅ Bonne qualité";
} else {
    echo "⚠️ Qualité insuffisante";
}
```

### Obtenir le Label de Qualité

```php
// Obtenir le label lisible de la qualité
$label = $this->recolteManager->getQualiteLabel($recolte->getQualite());

echo "Qualité: " . $label;
// Résultat: "Excellente", "Bonne", "Moyenne", ou "Mauvaise"
```

---

## Exemples Pratiques

### Exemple 1: Créer une Culture et Suivre sa Progression

```php
public function createAndTrackCulture(
    CultureManager $cultureManager,
    Parcelle $parcelle
): void {
    // Créer la culture
    $culture = $cultureManager->createCulture(
        'Blé',
        new \DateTime('2024-02-01'),
        new \DateTime('2024-07-01'),
        $parcelle
    );
    
    // Afficher l'état initial
    echo "Culture créée: " . $culture->getType_culture();
    echo "État: " . $culture->getEtat_croissance();
    
    // Afficher la progression
    $progress = $cultureManager->getProgressPercentage($culture);
    echo "Progression: " . round($progress, 2) . "%";
    
    // Vérifier si elle est en retard
    if ($cultureManager->isDelayed($culture)) {
        echo "⚠️ Culture en retard!";
    }
}
```

### Exemple 2: Créer une Récolte et Analyser le Rendement

```php
public function createAndAnalyzeRecolte(
    RecolteManager $recolteManager,
    Utilisateur $user,
    Culture $culture
): void {
    // Créer la récolte
    $recolte = $recolteManager->createRecolte(
        200.0,
        new \DateTime('2024-06-15'),
        'excellente',
        'Blé',
        'Champ Sud',
        $user,
        $culture
    );
    
    // Afficher les informations
    echo "Récolte créée: " . $recolte->getQuantite() . " kg";
    echo "Qualité: " . $recolteManager->getQualiteLabel($recolte->getQualite());
    
    // Calculer le rendement
    $yield = $recolteManager->calculateYield($recolte);
    echo "Rendement: " . round($yield, 2) . " kg/hectare";
    
    // Vérifier la qualité
    if ($recolteManager->isGoodQuality($recolte)) {
        echo "✅ Excellente récolte!";
    }
}
```

### Exemple 3: Gérer Plusieurs Cultures

```php
public function manageCultures(
    CultureManager $cultureManager,
    CultureRepository $cultureRepository
): void {
    // Récupérer toutes les cultures
    $cultures = $cultureRepository->findAll();
    
    foreach ($cultures as $culture) {
        // Calculer l'état
        $state = $cultureManager->calculateGrowthState($culture);
        
        // Obtenir la progression
        $progress = $cultureManager->getProgressPercentage($culture);
        
        // Afficher les informations
        echo sprintf(
            "%s: %s (%.1f%%) - %s\n",
            $culture->getType_culture(),
            $state,
            $progress,
            $cultureManager->isDelayed($culture) ? "⚠️ EN RETARD" : "✅ À jour"
        );
    }
}
```

### Exemple 4: Valider les Données Avant Création

```php
public function validateBeforeCreating(
    CultureManager $cultureManager,
    RecolteManager $recolteManager
): void {
    // Valider les dates de culture
    try {
        $cultureManager->validateDates(
            new \DateTime('2024-01-01'),
            new \DateTime('2024-06-01')
        );
        echo "✅ Dates de culture valides";
    } catch (\InvalidArgumentException $e) {
        echo "❌ " . $e->getMessage();
    }
    
    // Valider la quantité de récolte
    try {
        $recolteManager->validateQuantite(150.5);
        echo "✅ Quantité valide";
    } catch (\InvalidArgumentException $e) {
        echo "❌ " . $e->getMessage();
    }
    
    // Valider la date de récolte
    try {
        $recolteManager->validateDateRecolte(new \DateTime('2024-05-15'));
        echo "✅ Date de récolte valide";
    } catch (\InvalidArgumentException $e) {
        echo "❌ " . $e->getMessage();
    }
    
    // Valider la qualité
    try {
        $recolteManager->validateQualite('bonne');
        echo "✅ Qualité valide";
    } catch (\InvalidArgumentException $e) {
        echo "❌ " . $e->getMessage();
    }
}
```

---

## Gestion des Erreurs

### Erreurs Possibles

#### CultureManager

```php
// Erreur: Dates invalides
try {
    $cultureManager->createCulture(
        'Maïs',
        new \DateTime('2024-06-01'),
        new \DateTime('2024-01-01'), // Avant la plantation!
        $parcelle
    );
} catch (\InvalidArgumentException $e) {
    // Message: "La date de récolte prévue doit être supérieure à la date de plantation."
}

// Erreur: Type vide
try {
    $cultureManager->createCulture(
        '', // Type vide!
        new \DateTime('2024-01-01'),
        new \DateTime('2024-06-01'),
        $parcelle
    );
} catch (\InvalidArgumentException $e) {
    // Message: "La culture créée ne respecte pas les contraintes de validation: ..."
}
```

#### RecolteManager

```php
// Erreur: Quantité négative
try {
    $recolteManager->createRecolte(
        -50.0, // Quantité négative!
        new \DateTime('2024-05-15'),
        'bonne',
        'Maïs',
        'Champ Nord',
        $user
    );
} catch (\InvalidArgumentException $e) {
    // Message: "La quantité doit être strictement supérieure à 0."
}

// Erreur: Date dans le futur
try {
    $recolteManager->createRecolte(
        100.0,
        new \DateTime('+30 days'), // Date future!
        'bonne',
        'Maïs',
        'Champ Nord',
        $user
    );
} catch (\InvalidArgumentException $e) {
    // Message: "La date de récolte ne peut pas être dans le futur."
}

// Erreur: Qualité invalide
try {
    $recolteManager->validateQualite('invalid_quality');
} catch (\InvalidArgumentException $e) {
    // Message: "La qualité doit être l'une des valeurs suivantes: excellente, bonne, moyenne, mauvaise"
}
```

### Bonnes Pratiques

```php
// ✅ BON: Valider avant de créer
try {
    $cultureManager->validateDates($datePlantation, $dateRecolte);
    $culture = $cultureManager->createCulture(...);
} catch (\InvalidArgumentException $e) {
    // Afficher l'erreur à l'utilisateur
    $this->addFlash('error', $e->getMessage());
}

// ❌ MAUVAIS: Ne pas gérer les erreurs
$culture = $cultureManager->createCulture(...); // Peut lever une exception!

// ✅ BON: Utiliser les validateurs du service
if (!$recolteManager->isGoodQuality($recolte)) {
    // Faire quelque chose
}

// ❌ MAUVAIS: Accéder directement aux propriétés
if ($recolte->getQualite() === 'bonne') {
    // Peut être fragile
}
```

---

## Résumé

| Service | Méthode | Utilité |
|---------|---------|---------|
| CultureManager | `createCulture()` | Créer une culture validée |
| CultureManager | `validate()` | Valider une culture |
| CultureManager | `validateDates()` | Valider les dates |
| CultureManager | `calculateGrowthState()` | Calculer l'état |
| CultureManager | `isDelayed()` | Détecter les retards |
| CultureManager | `getProgressPercentage()` | Obtenir la progression |
| RecolteManager | `createRecolte()` | Créer une récolte validée |
| RecolteManager | `validate()` | Valider une récolte |
| RecolteManager | `validateQuantite()` | Valider la quantité |
| RecolteManager | `validateDateRecolte()` | Valider la date |
| RecolteManager | `validateQualite()` | Valider la qualité |
| RecolteManager | `calculateYield()` | Calculer le rendement |
| RecolteManager | `isGoodQuality()` | Vérifier la qualité |
| RecolteManager | `getQualiteLabel()` | Obtenir le label |

---

**Pour plus d'informations, consultez les tests unitaires dans `tests/Service/`**
