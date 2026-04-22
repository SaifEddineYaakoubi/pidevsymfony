# 🤖 Installation du Module IA de Prédiction des Ventes

## 📋 Prérequis

### 1. Python 3.8 ou supérieur

**Vérifier si Python est installé:**
```bash
python --version
# ou
python3 --version
```

**Si Python n'est pas installé:**
- **Windows**: Télécharger depuis https://www.python.org/downloads/
- **Linux**: `sudo apt install python3 python3-pip`
- **macOS**: `brew install python3`

### 2. pip (gestionnaire de paquets Python)

Normalement installé avec Python. Vérifier:
```bash
pip --version
# ou
pip3 --version
```

## 🚀 Installation des Dépendances Python

### Méthode 1: Avec requirements.txt (Recommandé)

```bash
pip install -r requirements.txt
```

### Méthode 2: Installation manuelle

```bash
pip install mysql-connector-python pandas numpy scikit-learn
```

## ✅ Vérification de l'Installation

### 1. Vérifier les dépendances via Symfony

Accéder à: `http://localhost:8000/dashboard/check-dependencies`

Cette route retourne un JSON avec l'état des dépendances:
```json
{
  "success": true,
  "python_installed": true,
  "python_path": "python3",
  "installed_packages": [
    "mysql-connector-python",
    "pandas",
    "numpy",
    "scikit-learn"
  ],
  "missing_packages": [],
  "error": null
}
```

### 2. Tester le script Python manuellement

```bash
python scripts/predict_sales.py 127.0.0.1 smartfarm root ""
```

**Résultat attendu:**
```json
{
  "success": true,
  "prediction": 5432.50,
  "predicted_month": "2026-05",
  "statistics": {
    "moyenne_mensuelle": 4850.25,
    "dernier_mois": 5200.00,
    ...
  },
  "error": null
}
```

## 🎯 Utilisation

### Accéder au Dashboard IA

```
http://localhost:8000/dashboard
```

Le dashboard affiche:
- 🤖 Prédiction du CA du mois prochain
- 📊 Statistiques détaillées
- 📈 Tendance (hausse/baisse)
- 📉 Variation en pourcentage

### API JSON

Pour obtenir uniquement la prédiction en JSON:
```
http://localhost:8000/dashboard/prediction
```

## 🔧 Résolution de Problèmes

### Erreur: "Python n'est pas installé"

**Solution:**
1. Installer Python 3.8+
2. Ajouter Python au PATH système
3. Redémarrer le terminal/serveur

### Erreur: "Dépendances Python manquantes"

**Solution:**
```bash
pip install mysql-connector-python pandas numpy scikit-learn
```

### Erreur: "Pas assez de données historiques"

**Solution:**
- Créer au moins 2 mois de ventes dans la base de données
- Les ventes doivent avoir des dates différentes (mois différents)

### Erreur: "Erreur de connexion à la base de données"

**Solution:**
1. Vérifier que MySQL est démarré
2. Vérifier les identifiants dans `.env`
3. Tester la connexion manuellement:
```bash
mysql -u root -p smartfarm
```

### Erreur: "Script Python introuvable"

**Solution:**
- Vérifier que le fichier `scripts/predict_sales.py` existe
- Vérifier les permissions du fichier (doit être exécutable)

## 📊 Données Requises

Le modèle nécessite:
- **Minimum**: 2 mois de données de ventes
- **Recommandé**: 6+ mois pour une meilleure précision
- **Optimal**: 12+ mois pour des prédictions fiables

## 🎓 Comment ça Marche?

1. **Récupération des données**: Le script Python se connecte à MySQL et récupère les ventes groupées par mois
2. **Préparation**: Les données sont transformées en format numérique
3. **Entraînement**: Un modèle de régression linéaire est entraîné sur l'historique
4. **Prédiction**: Le modèle prédit le CA du mois suivant
5. **Statistiques**: Calcul de la tendance, variation, moyenne, etc.

## 🔒 Sécurité

- Les identifiants de base de données sont passés en arguments (non hardcodés)
- Gestion complète des erreurs de connexion
- Timeout de 30 secondes pour éviter les blocages
- Logs détaillés pour le débogage

## 📈 Améliorer la Précision

Pour améliorer la précision des prédictions:
1. Ajouter plus de données historiques
2. Créer des ventes régulièrement
3. Éviter les mois sans ventes (créer au moins 1 vente par mois)

## 🆘 Support

Si vous rencontrez des problèmes:
1. Vérifier les logs Symfony: `var/log/dev.log`
2. Tester le script Python manuellement
3. Vérifier la route `/dashboard/check-dependencies`
