# 🔄 Réactiver WebAuthn après installation de Sodium

## ⚠️ État actuel

WebAuthn a été **temporairement désactivé** pour permettre la connexion normale sans l'extension Sodium.

Vous pouvez maintenant vous connecter avec email/mot de passe normalement.

## 📋 Pour réactiver WebAuthn (après avoir installé Sodium)

### Étape 1 : Installer l'extension Sodium

Suivez les instructions dans `ACTIVER_SODIUM.md` ou `SOLUTION_SODIUM.txt`

**Résumé rapide :**
1. Ouvrez `C:\xampp\php\php.ini`
2. Cherchez `;extension=sodium`
3. Supprimez le `;` pour obtenir `extension=sodium`
4. Sauvegardez
5. Redémarrez Apache
6. Vérifiez : `php -m | findstr sodium`

### Étape 2 : Réactiver le bundle WebAuthn

**A. Réactiver le bundle**

Ouvrez `config/bundles.php` et décommentez la ligne :

**Avant :**
```php
// Webauthn\Bundle\WebauthnBundle::class => ['all' => true], // Désactivé temporairement
```

**Après :**
```php
Webauthn\Bundle\WebauthnBundle::class => ['all' => true],
```

**B. Réactiver la configuration**

Renommez le fichier de configuration :

```bash
Rename-Item -Path "config/packages/webauthn.yaml.disabled" -NewName "webauthn.yaml"
```

Ou manuellement : renommez `webauthn.yaml.disabled` en `webauthn.yaml`

### Étape 3 : Réactiver le bouton Face ID

Ouvrez `templates/admin/auth/login.html.twig` et décommentez le bloc Face ID :

**Cherchez :**
```html
<!-- WebAuthn temporairement désactivé - Activez l'extension Sodium d'abord -->
<!--
<div class="divider">
    <span>OU</span>
</div>

<button type="button" id="faceIdLoginBtn" ...>
    ...
</button>
...
-->
```

**Remplacez par :**
```html
<div class="divider">
    <span>OU</span>
</div>

<button type="button" id="faceIdLoginBtn" class="btn-login" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
    <i class="fas fa-fingerprint me-2"></i>Se connecter avec Face ID / Touch ID
</button>

<div id="faceIdMessage" class="mt-3"></div>
```

### Étape 4 : Vider le cache et redémarrer

```bash
php bin/console cache:clear
symfony server:restart
# OU
# Redémarrez manuellement votre serveur
```

### Étape 5 : Tester

1. Allez sur `http://localhost:8000/admin/login` (utilisez **localhost**, pas 127.0.0.1)
2. Le bouton "Se connecter avec Face ID" devrait être visible
3. Connectez-vous normalement avec email/mot de passe
4. Allez sur `/profile/setup-faceid` pour configurer Face ID
5. Testez la connexion avec Face ID

## ✅ Vérification rapide

Avant de réactiver, vérifiez que Sodium fonctionne :

```bash
php -r "echo extension_loaded('sodium') ? '✓ Sodium OK' : '✗ Sodium KO';"
```

Si vous voyez `✓ Sodium OK`, vous pouvez réactiver WebAuthn !

## 🚨 En cas de problème après réactivation

Si vous avez des erreurs après avoir réactivé WebAuthn :

1. Vérifiez que Sodium est bien chargé : `php -m | findstr sodium`
2. Videz le cache : `php bin/console cache:clear`
3. Redémarrez Apache
4. Redémarrez le serveur Symfony

Si ça ne fonctionne toujours pas, désactivez à nouveau WebAuthn et contactez le support.
