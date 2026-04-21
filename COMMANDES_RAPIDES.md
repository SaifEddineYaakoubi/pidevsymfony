# 🚀 Commandes rapides pour démarrer

## Installation complète en 3 commandes

```bash
# 1. Mettre à jour la base de données
php bin/console doctrine:schema:update --force

# 2. Vider le cache
php bin/console cache:clear

# 3. Démarrer le serveur sur localhost (IMPORTANT !)
symfony server:start
# OU si vous n'avez pas Symfony CLI :
php -S localhost:8000 -t public/
```

## Ouvrir l'application

```
http://localhost:8000
```

⚠️ **IMPORTANT** : Utilisez `localhost` et PAS `127.0.0.1` !

## Test rapide

1. **Inscription** : `http://localhost:8000/register`
2. **Login** : `http://localhost:8000/admin/login`
3. **Configurer Face ID** : `http://localhost:8000/profile/setup-faceid`

## Réinitialiser la base de données (développement uniquement)

```bash
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force
```

## Vérifier que tout fonctionne

```bash
# Vérifier les routes
php bin/console debug:router | grep webauthn

# Devrait afficher :
# webauthn_register_options  POST  /webauthn/register/options
# webauthn_register_verify   POST  /webauthn/register/verify
# webauthn_login_options     POST  /webauthn/login/options
# webauthn_login_verify      POST  /webauthn/login/verify
```

## En cas de problème

### Erreur "Bundle not found"

```bash
composer install
php bin/console cache:clear
```

### Erreur "Table doesn't exist"

```bash
php bin/console doctrine:schema:update --force
```

### WebAuthn ne fonctionne pas

1. Vérifiez que vous utilisez `http://localhost:8000` (pas 127.0.0.1)
2. Ouvrez la console du navigateur (F12) pour voir les erreurs
3. Vérifiez que votre navigateur supporte WebAuthn : https://caniuse.com/webauthn

## Production

Avant de déployer en production :

1. Modifiez `config/packages/webauthn.yaml` :
```yaml
rp:
    name: 'VotreApp'
    id: 'votredomaine.com'  # Changez ici !
```

2. Activez HTTPS (obligatoire en production)

3. Testez sur plusieurs appareils
