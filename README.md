# 🌱 SmartFarm — Plateforme Agricole Intelligente

SmartFarm est une application web développée avec **Symfony 6.4** pour la gestion intelligente des exploitations agricoles. Elle intègre des fonctionnalités d'IA, de géolocalisation, de gestion des stocks, des ventes, des récoltes et bien plus.

---

## 🚀 Fonctionnalités

### 👤 Authentification & Utilisateurs
- Inscription / Connexion classique (email + mot de passe)
- Connexion via **Google OAuth2**
- Connexion par **reconnaissance faciale** (Face API)
- Réinitialisation de mot de passe par email
- Gestion des rôles : `ROLE_ADMIN`, `ROLE_AGRICULTEUR`, `ROLE_CLIENT`

### 🛡️ Administration
- Dashboard admin avec statistiques en temps réel
- Gestion des utilisateurs, stocks, produits, ventes, clients
- Analyse IA des utilisateurs (`/admin/ai-analytics`)
- Messagerie interne entre utilisateurs
- Sidebar dynamique avec accès rapide à toutes les sections

### 🌾 Gestion Agricole
- **Parcelles** : suivi des parcelles avec météo en temps réel (OpenWeather API)
- **Cultures** : cycle de vie, automatisation, prédictions IA
- **Récoltes** : saisie, archivage, export CSV
- **Rendements** : calcul et visualisation des rendements par parcelle
- **Analyse du sol** : intégration AgroAPI + HuggingFace pour recommandations IA

### 📦 Stocks & Produits
- CRUD complet des stocks et produits
- Alertes automatiques par email (Brevo/Gmail) quand le stock est bas
- Tri, recherche et filtrage dynamique (Stimulus/Turbo)
- QR Code par vente (endroid/qr-code)

### 🛒 Ventes & Clients
- Gestion des ventes avec géolocalisation de livraison
- Conversion de devises en temps réel (ExchangeRate API)
- Système de badges clients (Bronze, Silver, Gold)
- Génération de QR codes pour chaque vente
- Export PDF des ventes (DomPDF)

### 🤖 Intelligence Artificielle
- Prédictions de rendement (modèle ML local)
- Analyse des statistiques utilisateurs (HuggingFace API)
- Analyse du sol avec recommandations personnalisées
- Prévisions des ventes

### 💬 Messagerie
- Messagerie interne entre utilisateurs
- Interface temps réel

---

## 🛠️ Stack Technique

| Couche | Technologie |
|---|---|
| Backend | PHP 8.1+, Symfony 6.4 |
| Base de données | MariaDB / MySQL 8 |
| ORM | Doctrine ORM 3 |
| Frontend | Twig, Bootstrap 5, Stimulus, Turbo |
| Assets | Symfony AssetMapper + Webpack Encore |
| Auth | Symfony Security, KnpU OAuth2, Face API |
| Emails | Symfony Mailer, Brevo, Gmail |
| IA / ML | HuggingFace API, AgroAPI, modèle local |
| QR Code | endroid/qr-code v6 (ext-gd requis) |
| PDF | DomPDF |
| Géoloc | OpenWeather, Nominatim, IPGeolocation |

---

## ⚙️ Installation

### Prérequis
- PHP >= 8.1 avec extensions : `gd`, `sodium`, `pdo_mysql`, `intl`, `ctype`, `iconv`
- Composer
- Node.js + npm
- MariaDB ou MySQL 8

### Étapes

```bash
# 1. Cloner le projet
git clone https://github.com/SaifEddineYaakoubi/pidevsymfony.git
cd pidevsymfony

# 2. Installer les dépendances PHP
composer install

# 3. Installer les dépendances JS
npm install

# 4. Configurer l'environnement
cp .env .env.local
# Éditer .env.local avec vos vraies valeurs (DB, API keys, etc.)

# 5. Créer la base de données et appliquer les migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 6. Compiler les assets
npm run build
# ou en développement :
npm run dev

# 7. Lancer le serveur
symfony server:start
# ou
php -S localhost:8000 -t public/
```

---

## 🔑 Variables d'environnement

Copier `.env` vers `.env.local` et renseigner les valeurs suivantes :

```dotenv
# Base de données
DATABASE_URL="mysql://user:password@127.0.0.1:3306/smartfarm?serverVersion=8.0.32"

# Mailer
MAILER_DSN=smtp://...
MAILER_FROM="SmartFarm <no-reply@example.com>"
MAILER_ADMIN=admin@example.com

# Google OAuth
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

# APIs
OPENWEATHER_API_KEY=your_key
HUGGINGFACE_API_KEY=your_key
AGRO_API_KEY=your_key
EXCHANGERATE_API_KEY=your_key
IPGEOLOCATION_API_KEY=your_key
PEXELS_API_KEY=your_key
UNSPLASH_ACCESS_KEY=your_key

# Seuil d'alerte stock
STOCK_SEUIL=5
```

---

## 🗄️ Structure du projet

```
src/
├── Controller/         # Contrôleurs (Admin, Agriculteur, API, Front...)
├── Entity/             # Entités Doctrine
├── Repository/         # Repositories
├── Service/            # Services métier (IA, QrCode, Géoloc, Devises...)
├── Form/               # Formulaires Symfony
└── Security/           # Authentification, voters

templates/
├── admin/              # Templates administration
├── agriculteur/        # Templates espace agriculteur
├── stock/              # Templates gestion stocks
└── ...

assets/
├── controllers/        # Contrôleurs Stimulus
├── styles/             # CSS
└── js/                 # JavaScript
```

---

## 👥 Équipe

Projet réalisé dans le cadre du cursus **ESPRIT** (École Supérieure Privée d'Ingénierie et de Technologies).

| Membre | Module |
|---|---|
| Saif Eddine Yaakoubi | Intégration, Admin, Auth |
| Maram Abdeladhim | Stocks, Alertes Brevo |
| Amine Ameur | Messagerie, Analyse IA, Face Login |
| Ahmed | Ventes, Clients, QR Code, Devises |

---

## 📄 Licence

Projet académique — usage interne ESPRIT uniquement.
