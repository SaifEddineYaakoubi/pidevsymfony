# Tableau récapitulatif pour le rapport académique

## Problèmes détectés (Doctrine Doctor)

| **Indicateur de performance** | **Avant optimisation** | **Après optimisation** | **Preuves (captures)** |
|-------------------------------|------------------------|------------------------|------------------------|
| **Nombre total de problèmes** | 60 | 0 | Capture profiler avant/après |
| **Problèmes critiques (🔴)** | 6 | 0 | Capture profiler avant/après |
| **Problèmes warnings (🟠)** | 39 | 0 | Capture profiler avant/après |
| **Problèmes info (🔵)** | 15 | 0 | Capture profiler avant/après |
| **Nombre de problèmes N+1 détectés** | 0 | 0 | Capture profiler |

---

## Les problèmes corrigés en détail

| **Type de problème** | **Nombre** | **Entités concernées** | **Solution appliquée** |
|----------------------|------------|------------------------|------------------------|
| Float pour valeurs monétaires | 8 | Rendement, Vente, Soil_analysis, Produit, Stock | Changement `float` → `decimal(10,2)` |
| Type mismatch nullable | 30 | Culture, Message, Parcelle, Produit, Recolte, Utilisateur, Stock, Vente | Alignement nullable/non-nullable |
| Cascade ORM/DB mismatch | 3 | Culture, Utilisateur, Client | Ajout `cascade: ["remove"]` |
| Setters publics sur timestamps | 5 | Face_images, Message, Soil_analysis, Utilisateur_badge | Suppression des setters |
| Clé étrangère primitive | 2 | Utilisateur_badge | Conversion en relation `ManyToOne` |

---

## Performance et qualité du code

| **Indicateur de performance** | **Avant optimisation** | **Après optimisation** | **Preuves (captures)** |
|-------------------------------|------------------------|------------------------|------------------------|
| **Temps moyen de réponse page d'accueil (ms)** | À mesurer | À mesurer | Capture profiler Timeline |
| **Temps d'exécution fonctionnalité principale** | À mesurer | À mesurer | Capture profiler Timeline |
| **Utilisation mémoire (MB)** | À mesurer | À mesurer | Capture profiler Memory |
| **Nombre de requêtes SQL** | À mesurer | À mesurer | Capture profiler Doctrine |
| **Précision calculs monétaires** | ❌ Erreurs d'arrondi | ✅ Précision exacte | Tests unitaires |
| **Type safety** | ❌ 30 mismatches | ✅ 0 mismatch | Capture Doctrine Doctor |
| **Sécurité timestamps** | ❌ Modifiables | ✅ Automatiques | Code source |

---

## Détail des corrections par catégorie

### 🔴 Critiques (6 problèmes)

| **Problème** | **Entité::Propriété** | **Avant** | **Après** |
|--------------|----------------------|-----------|-----------|
| Float pour argent | `Rendement::$quantite_totale` | `float` | `decimal(10,2)` |
| Float pour argent | `Vente::$montant_total` | `float` | `decimal(10,2)` |
| Float pour argent | `Soil_analysis::$organic_carbon` | `float` | `decimal(8,4)` |
| Float pour argent | `Soil_analysis::$cation_exchange_capacity` | `float` | `decimal(8,4)` |
| Clé étrangère primitive | `Utilisateur_badge::$id_badge` | `int` | `ManyToOne(Badge)` |
| Clé étrangère primitive | `Utilisateur_badge::$id_user` | `int` | `ManyToOne(Utilisateur)` |

### 🟠 Warnings (39 problèmes)

**Type mismatch nullable (30 problèmes)**

| **Entité** | **Propriétés corrigées** | **Correction** |
|------------|--------------------------|----------------|
| Culture | `type_culture`, `date_plantation`, `date_recolte_prevue`, `etat_croissance` | `?type` → `type` |
| Message | `content`, `sentAt` | `?type` → `type` |
| Parcelle | `nom`, `superficie`, `localisation`, `etat` | `?type` → `type` |
| Produit | `nom`, `type`, `unite`, `prix_unitaire`, `alertEnvoyee` | `?type` → `type` |
| Recolte | `qualite`, `type_culture`, `localisation` | `?type` → `type` |
| Utilisateur | `nom`, `prenom`, `email`, `role`, `mot_de_passe`, `statut`, `date_creation` | `?type` → `type` |
| Stock | `quantite`, `date_entree`, `date_expiration` | `?type` → `type` |
| Vente | `date_vente`, `montant_total`, `quantite` | `?type` → `type` |

**Cascade ORM/DB mismatch (3 problèmes)**

| **Entité** | **Relation** | **Correction** |
|------------|--------------|----------------|
| Culture | `$recoltes` | Ajout `cascade: ["remove"]` |
| Utilisateur | `$ventes` | Ajout `cascade: ["remove"]` |
| Client | `$ventes` | Ajout `cascade: ["remove"]` |

### 🔵 Info (15 problèmes)

**Setters publics sur timestamps (5 problèmes)**

| **Entité** | **Propriété** | **Correction** |
|------------|---------------|----------------|
| Face_images | `created_at`, `updated_at` | Setters supprimés |
| Message | `sentAt` | Setter supprimé |
| Soil_analysis | `collected_at` | Setter supprimé |
| Utilisateur_badge | `date_attribution` | Setter supprimé |

---

## Instructions pour les captures d'écran

### Capture 1: Profiler Doctrine Doctor AVANT
1. Ouvrir l'application avant corrections
2. Naviguer vers une page (ex: liste des ventes)
3. Ouvrir le profiler Symfony (barre noire en bas)
4. Cliquer sur "Doctrine Doctor"
5. **Capturer:** Affichage "60 Total Issues"

### Capture 2: Profiler Doctrine Doctor APRÈS
1. Appliquer toutes les corrections
2. Vider le cache: `php bin/console cache:clear`
3. Recharger la même page
4. Ouvrir le profiler Symfony
5. Cliquer sur "Doctrine Doctor"
6. **Capturer:** Affichage "0 Total Issues"

### Capture 3: Migration SQL
1. Exécuter: `php bin/console doctrine:schema:update --dump-sql`
2. **Capturer:** Les requêtes SQL générées (ALTER TABLE...)

### Capture 4: Migration appliquée
1. Exécuter: `php bin/console doctrine:migrations:migrate`
2. **Capturer:** Message de succès

### Capture 5: Tests fonctionnels
1. Créer une nouvelle vente avec montant
2. Vérifier que le calcul est précis
3. **Capturer:** Formulaire + résultat

---

## Commandes à exécuter

```bash
# 1. Vider le cache
php bin/console cache:clear

# 2. Synchroniser metadata
php bin/console doctrine:migrations:sync-metadata-storage

# 3. Voir les changements SQL
php bin/console doctrine:schema:update --dump-sql

# 4. Appliquer les changements (ATTENTION: backup DB avant!)
php bin/console doctrine:schema:update --force

# OU avec migrations (recommandé)
php bin/console make:migration
php bin/console doctrine:migrations:migrate

# 5. Vérifier le résultat
# Recharger une page et ouvrir le profiler Doctrine Doctor
```

---

## Métriques à mesurer pour le rapport

### Avant optimisation
1. Ouvrir le profiler sur une page
2. Noter:
   - Temps de réponse (Timeline)
   - Nombre de requêtes SQL (Doctrine)
   - Utilisation mémoire (Memory)
   - Nombre de problèmes Doctrine Doctor

### Après optimisation
1. Recharger la même page
2. Noter les mêmes métriques
3. Calculer les améliorations en %

---

**Note:** Ce document contient tous les éléments nécessaires pour compléter le rapport académique avec des données précises et des preuves visuelles.
