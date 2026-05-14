# 🌱 SmartFarm — Plateforme Agricole Intelligente

> Une application web full-stack pour la gestion intelligente des exploitations agricoles, combinant **Symfony 6.4** (web) et **JavaFX** (desktop), avec IA, géolocalisation, reconnaissance faciale et bien plus.

---

## 📋 Table des Matières

- [Description du Projet](#description-du-projet)
- [Fonctionnalités](#fonctionnalités)
- [Stack Technique](#stack-technique)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Structure du Projet](#structure-du-projet)
- [Équipe](#équipe)
- [Contribution](#contribution)
- [Licence](#licence)

---

## 📖 Description du Projet

**SmartFarm** est une plateforme agricole intelligente développée dans le cadre du projet intégré **ESPRIT** (École Supérieure Privée d'Ingénierie et de Technologies).

### 🎯 Objectif

Moderniser la gestion des exploitations agricoles en offrant aux agriculteurs et administrateurs un outil centralisé, intelligent et accessible depuis le web **et** le bureau.

### 🔍 Problème résolu

Les agriculteurs manquent d'outils numériques intégrés pour suivre leurs cultures, stocks, ventes et analyses de sol en temps réel. SmartFarm centralise toutes ces données avec des recommandations IA pour optimiser les rendements.

### ✨ Principales Fonctionnalités

- 🔐 Authentification multi-méthodes (classique, Google OAuth2, reconnaissance faciale)
- 🌾 Gestion complète des parcelles, cultures, récoltes et rendements
- 📦 Suivi des stocks avec alertes automatiques par email
- 🤖 Analyse IA des données utilisateurs et prédictions de rendement
- 🛒 Gestion des ventes avec conversion de devises et export PDF
- 💬 Messagerie interne entre utilisateurs
- 🖥️ Application desktop JavaFX connectée au même backend

---

## 🚀 Fonctionnalités

### 👤 Authentification & Utilisateurs
- Inscription / Connexion classique (email + mot de passe)
- Connexion via **Google OAuth2**
- Connexion par **reconnaissance faciale** (Face-API.js + TensorFlow.js)
- Réinitialisation de mot de passe par email
- Gestion des rôles : `ROLE_ADMIN`, `ROLE_AGRICULTEUR`, `ROLE_CLIENT`

### 🛡️ Administration
- Dashboard admin avec statistiques en temps réel
- Gestion des utilisateurs, stocks, produits, ventes, clients
- Analyse IA des utilisateurs (`/admin/ai-analytics`)
- Messagerie interne entre utilisateurs
- Sidebar dynamique avec accès rapide à toutes les sections

### 🌾 Gestion Agricole
- **Parcelles** : suivi avec météo en temps réel (OpenWeather API)
- **Cultures** : cycle de vie, automatisation, prédictions IA
- **Récoltes** : saisie, archivage, export CSV
- **Rendements** : calcul et visualisation par parcelle
- **Analyse du sol** : intégration AgroAPI + HuggingFace pour recommandations IA

### 📦 Stocks & Produits
- CRUD complet des stocks et produits
- Alertes automatiques par email (Brevo/Gmail) quand le stock est bas
- Tri, recherche et filtrage dynamique (Stimulus/Turbo)
- QR Code par produit (endroid/qr-code)

### 🛒 Ventes & Clients
- Gestion des ventes avec géolocalisation de livraison
- Conversion de devises en temps réel (ExchangeRate API)
- Système de badges clients (Bronze, Silver, Gold)
- Export PDF des ventes (DomPDF)

### 🤖 Intelligence Artificielle
- Prédictions de rendement (modèle ML local)
- Analyse statistique des utilisateurs (HuggingFace API)
- Analyse du sol avec recommandations personnalisées
- Prévisions des ventes

---

## 🛠️ Stack Technique

| Couche | Technologie |
|---|---|
| Backend Web | PHP 8.1+, Symfony 6.4 |
| Desktop | JavaFX |
| Base de données | MariaDB / MySQL 8 |
| ORM | Doctrine ORM 3 |
| Frontend | Twig, Bootstrap 5, Stimulus, Turbo |
| Assets | Symfony AssetMapper + Webpack Encore |
| Auth | Symfony Security, KnpU OAuth2, Face-API.js |
| Emails | Symfony Mailer, Brevo, Gmail |
| IA / ML | HuggingFace API, AgroAPI, modèle local |
| QR Code | endroid/qr-code v6 |
| PDF | DomPDF |
| Géolocalisation | OpenWeather, Nominatim, IPGeolocation |

---

## ⚙️ Installation

### Prérequis

Avant de commencer, assurez-vous d'avoir installé :

* **PHP >= 8.1** avec les extensions : `gd`, `sodium`, `pdo_mysql`, `intl`, `ctype`, `iconv`
* **Composer** — [getcomposer.org](https://getcomposer.org)
* **Node.js >= 18** et **npm** — [nodejs.org](https://nodejs.org)
* **MariaDB** ou **MySQL 8**
* **Symfony CLI** (recommandé) — [symfony.com/download](https://symfony.com/download)

### Étapes

1. Clonez le repository :

```bash
git clone https://github.com/SaifEddineYaakoubi/pidevsymfony.git
cd pidevsymfony
```

1. Installez les dépendances PHP :

```bash
composer install
```

1. Installez les dépendances JavaScript :

```bash
npm install
```

1. Configurez l'environnement (voir section [Configuration](#configuration)) :

```bash
cp .env .env.local
```

1. Créez la base de données et appliquez les migrations :

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

1. Compilez les assets :

```bash
# Production
npm run build

# Développement (avec watcher)
npm run dev
```

1. Lancez le serveur de développement :

```bash
symfony server:start
```

L'application est accessible sur **[http://localhost:8000](http://localhost:8000)**

---

## 🔑 Configuration

Copiez `.env` vers `.env.local` et renseignez les variables suivantes :

```dotenv
# Base de données
DATABASE_URL="mysql://user:password@127.0.0.1:3306/smartfarm?serverVersion=8.0.32"

# Mailer
MAILER_DSN=smtp://...
MAILER_FROM="SmartFarm <no-reply@example.com>"
MAILER_ADMIN=admin@example.com

# Google OAuth2
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret

# APIs externes
OPENWEATHER_API_KEY=your_key
HUGGINGFACE_API_KEY=your_key
AGRO_API_KEY=your_key
EXCHANGERATE_API_KEY=your_key
IPGEOLOCATION_API_KEY=your_key
PEXELS_API_KEY=your_key

# Seuil d'alerte stock
STOCK_SEUIL=5
```

> ⚠️ Ne commitez **jamais** votre fichier `.env.local` — il est déjà dans `.gitignore`.

---

## 💻 Utilisation

### Accès à l'application

| Rôle | URL | Identifiants par défaut |
|---|---|---|
| Admin | `/admin/dashboard` | Créer via `php bin/console app:create-admin` |
| Agriculteur | `/agriculteur/dashboard` | Inscription via `/register` |
| Client | `/` | Inscription via `/register` |

### Reconnaissance Faciale

1. Rendez-vous sur `/admin/register`
2. Cliquez sur **"Configurer la reconnaissance faciale"**
3. Autorisez l'accès à la caméra et enregistrez votre visage
4. Connectez-vous ensuite via `/admin/login` → **"Se connecter avec Face ID"**

### Analyse IA

* Connectez-vous en tant qu'**admin**
* Accédez à **Sidebar → "Analyse IA"** ou directement via `/admin/ai-analytics`
* Consultez les statistiques, graphiques et recommandations générées automatiquement

### PHP — Vérification de l'environnement

Pour vérifier que **PHP** est correctement installé et que toutes les extensions requises sont actives :

```bash
php -v
php -m | grep -E "gd|sodium|pdo_mysql|intl"
```

> *Assurez-vous que les extensions listées apparaissent bien dans la sortie.*

Pour activer une extension manquante, décommentez la ligne correspondante dans votre `php.ini` :

```ini
extension=gd
extension=sodium
```

---

## 🗄️ Structure du Projet

```
pidevsymfony/
├── assets/
│   ├── controllers/        # Contrôleurs Stimulus (JS)
│   ├── styles/             # Feuilles de style CSS
│   └── js/                 # Scripts JavaScript
├── config/
│   ├── packages/           # Configuration des bundles Symfony
│   └── routes/             # Définition des routes
├── public/
│   ├── models/             # Modèles Face-API.js (TensorFlow)
│   └── js/                 # Scripts publics
├── src/
│   ├── Controller/         # Contrôleurs (Admin, Agriculteur, API, Front...)
│   ├── Entity/             # Entités Doctrine
│   ├── Repository/         # Repositories
│   ├── Service/            # Services métier (IA, QrCode, Géoloc, Devises...)
│   ├── Form/               # Formulaires Symfony
│   └── Security/           # Authentification, voters
├── templates/
│   ├── admin/              # Templates administration
│   ├── agriculteur/        # Templates espace agriculteur
│   └── stock/              # Templates gestion stocks
├── tests/                  # Tests unitaires PHPUnit
├── .env                    # Variables d'environnement (template)
├── composer.json           # Dépendances PHP
└── package.json            # Dépendances JavaScript
```

---

## 👥 Équipe

Projet réalisé dans le cadre du cursus **ESPRIT** — École Supérieure Privée d'Ingénierie et de Technologies.

| Membre | Module |
|---|---|
| **Saif Eddine Yaakoubi** | Intégration, parcelles , cultures |
| **Maram Abdeladhim** | Stocks, Alertes Email, Brevo |
| **Maram dhambri** | recoltes|
| **Amine Ameur** | Messagerie, Analyse IA, Reset Password |
| **Ahmed boussaidi** | Ventes, Clients, QR Code, Devises |

---

## 🤝 Contribution

Les contributions sont les bienvenues ! Voici comment participer :

1. **Forkez** le repository en cliquant sur le bouton *Fork* en haut à droite de la page GitHub

1. **Clonez** votre fork en local :

```bash
git clone https://github.com/votre-username/pidevsymfony.git
cd pidevsymfony
```

1. **Créez une branche** pour votre fonctionnalité :

```bash
git checkout -b feature/ma-nouvelle-fonctionnalite
```

1. **Effectuez vos modifications** et committez-les avec un message clair :

```bash
git add .
git commit -m "feat: ajout de la fonctionnalité X"
```

1. **Poussez** votre branche sur votre fork :

```bash
git push origin feature/ma-nouvelle-fonctionnalite
```

1. **Ouvrez une Pull Request** depuis GitHub vers la branche `main` de ce repository

### Bonnes pratiques

* Respectez les conventions de nommage existantes (PSR-12 pour PHP)
* Ajoutez des tests unitaires pour toute nouvelle fonctionnalité
* Documentez les nouvelles méthodes et classes
* Un commit = une modification logique

> 💡 Pour les bugs ou suggestions, ouvrez une [Issue](https://github.com/SaifEddineYaakoubi/pidevsymfony/issues) avant de soumettre une PR.

---

## 📄 Licence

Ce projet est distribué sous la licence **MIT**.

```
MIT License

Copyright (c) 2026 SmartFarm — ESPRIT

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
```

> Ce projet a été réalisé dans un cadre académique à **ESPRIT**. Toute réutilisation commerciale doit faire l'objet d'une autorisation préalable.

---

<p align="center">
  Made with ❤️ by the SmartFarm Team — ESPRIT 2026
</p>
