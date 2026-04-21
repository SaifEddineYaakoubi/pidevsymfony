# 🚀 Démarrage Rapide - Tester l'API AgroAPI

## ⚡ 5 Étapes pour Commencer

### Étape 1️⃣ : Installer les Dépendances (2 minutes)

```bash
cd C:\Users\admin\Desktop\pidevsymfony

composer require symfony/http-client

php bin/console cache:clear
```

### Étape 2️⃣ : Configurer les Variables (1 minute)

Éditer le fichier `.env` et ajouter :

```env
# Dans C:\Users\admin\Desktop\pidevsymfony\.env
AGRO_API_KEY=test_key_free_tier
```

### Étape 3️⃣ : Démarrer le Serveur (30 secondes)

```bash
# Depuis C:\Users\admin\Desktop\pidevsymfony

php -S 127.0.0.1:8000 -t public/
```

### Étape 4️⃣ : Accéder aux Pages de Test (30 secondes)

Ouvrir le navigateur et aller sur :

**Page de test - Analyse du sol:**
```
http://localhost:8000/test/soil
```

**Page de test - Simulation rendement:**
```
http://localhost:8000/test/simulation
```

**Page de test - Recommandations:**
```
http://localhost:8000/test/recommendations
```

**API JSON de test:**
```
http://localhost:8000/test/api/soil
```

### Étape 5️⃣ : Tester Avec des Données Réelles (5 minutes)

1. Se connecter: `http://localhost:8000/login`
2. Créer une récolte
3. Accéder à: `http://localhost:8000/soil/recolte/1`
4. Vérifier les données affichées

---

## 📋 URLs de Test Disponibles

| URL | Description | Mode |
|-----|-------------|------|
| `http://localhost:8000/test/soil` | Analyse sol (test) | Simulation |
| `http://localhost:8000/test/simulation` | 4 scénarios rendement | Simulation |
| `http://localhost:8000/test/recommendations` | Recommandations NPK | Simulation |
| `http://localhost:8000/test/api/soil` | API JSON (test) | JSON |
| `http://localhost:8000/test/api-connection` | Vérifier connexion | JSON |
| `http://localhost:8000/soil/recolte/1` | Analyse vraie récolte | Production |
| `http://localhost:8000/soil/rendement/1` | Impact sur rendement | Production |

---

## ✅ Checklist de Vérification

- [ ] HTTP Client installé: `composer show | findstr http-client`
- [ ] `.env` contient `AGRO_API_KEY`
- [ ] Serveur lancé: `http://localhost:8000` accessible
- [ ] Page test affichable: `http://localhost:8000/test/soil`
- [ ] Données simulées visibles
- [ ] Récolte créée: `http://localhost:8000/recolte/new`
- [ ] Analyse récolte fonctionnelle: `http://localhost:8000/soil/recolte/1`

---

## 🐛 Solutions Rapides

### ❌ Erreur 404 (Page non trouvée)
```bash
php bin/console cache:clear
php bin/console debug:router | findstr test
```

### ❌ Erreur 500 (Erreur serveur)
```bash
type var\log\dev.log
```

### ❌ Page blanche
```bash
php bin/console cache:clear

# Ou activer mode debug
set SYMFONY_DEBUG=1
php -S 127.0.0.1:8000 -t public/
```

---

## 📊 Données de Test Fournies

### Scénario 1: Excellent (90/100)
- Rendement: 4.8 kg/m²
- Efficacité: 95%
- Statut: Gardez les bonnes pratiques

### Scénario 2: Bon (75/100)
- Rendement: 3.5 kg/m²
- Efficacité: 83%
- Statut: Performance satisfaisante

### Scénario 3: Moyen (50/100)
- Rendement: 1.8 kg/m²
- Efficacité: 50%
- Statut: À améliorer

### Scénario 4: Critique (30/100)
- Rendement: 0.8 kg/m²
- Efficacité: 20%
- Statut: Action urgente

---

## 📞 Support Rapide

| Problème | Solution |
|----------|----------|
| "Composer not found" | Installer Composer: https://getcomposer.org |
| "PHP not found" | Vérifier PHP PATH ou réinstaller |
| "Port 8000 already in use" | Utiliser: `php -S 127.0.0.1:8001 -t public/` |
| "No route found" | Vérifier `.env` et clear cache |

---

## 🎯 Prochaines Étapes Après Test

1. ✅ Tester les pages de test
2. ✅ Créer une vraie récolte
3. ✅ Analyser le sol pour la récolte
4. ✅ Créer un rendement
5. ✅ Voir impact du sol sur rendement
6. ⏭️ Ajouter les boutons aux templates existants
7. ⏭️ Configurer vraie clé API AgroAPI
8. ⏭️ Activer les notifications

---

## 🚀 C'est Prêt !

Tout est installé et prêt à tester ! 

Rendez-vous sur: **`http://localhost:8000/test/soil`** 🎉

