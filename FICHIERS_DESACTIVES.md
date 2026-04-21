# 📁 Fichiers WebAuthn désactivés

## 🔴 Fichiers renommés (désactivés)

Ces fichiers ont été renommés avec l'extension `.disabled` pour désactiver WebAuthn :

### 1. Entité
```
src/Entity/PublicKeyCredentialSource.php.disabled
```
**Rôle** : Stocke les clés publiques WebAuthn (Passkeys)

### 2. Repository
```
src/Repository/PublicKeyCredentialSourceRepository.php.disabled
```
**Rôle** : Gère les opérations de base de données pour les Passkeys

### 3. Contrôleur
```
src/Controller/WebAuthnController.php.disabled
```
**Rôle** : Gère les endpoints WebAuthn (enregistrement et connexion)

### 4. Configuration
```
config/packages/webauthn.yaml.disabled
```
**Rôle** : Configuration du bundle WebAuthn

## 🔄 Pour réactiver WebAuthn

### Prérequis
1. ✅ Installer l'extension PHP Sodium
2. ✅ Vérifier : `php -m | findstr sodium`

### Étape 1 : Renommer les fichiers

```powershell
# Dans PowerShell
cd C:\Users\amine\OneDrive\Desktop\devPI

Rename-Item "src/Entity/PublicKeyCredentialSource.php.disabled" "PublicKeyCredentialSource.php"
Rename-Item "src/Repository/PublicKeyCredentialSourceRepository.php.disabled" "PublicKeyCredentialSourceRepository.php"
Rename-Item "src/Controller/WebAuthnController.php.disabled" "WebAuthnController.php"
Rename-Item "config/packages/webauthn.yaml.disabled" "webauthn.yaml"
```

### Étape 2 : Corriger l'entité PublicKeyCredentialSource

Ouvrez `src/Entity/PublicKeyCredentialSource.php` et changez :

**Ligne 12 - Avant :**
```php
private string $userHandle;
```

**Ligne 12 - Après :**
```php
public string $userHandle;
```

### Étape 3 : Restaurer l'entité Utilisateur

Ouvrez `src/Entity/Utilisateur.php` et ajoutez :

**1. Import (après les autres use) :**
```php
use Webauthn\PublicKeyCredentialUserEntity;
```

**2. Propriété (après date_creation) :**
```php
#[ORM\Column(type: 'string', length: 255, unique: true, nullable: true)]
private ?string $webauthnUserHandle = null;
```

**3. Méthodes (avant la dernière accolade }) :**
```php
public function getWebauthnUserHandle(): ?string
{
    return $this->webauthnUserHandle;
}

public function setWebauthnUserHandle(?string $webauthnUserHandle): self
{
    $this->webauthnUserHandle = $webauthnUserHandle;
    return $this;
}

public function getPublicKeyCredentialUserEntity(): PublicKeyCredentialUserEntity
{
    if ($this->webauthnUserHandle === null) {
        $this->webauthnUserHandle = base64_encode(random_bytes(32));
    }

    return new PublicKeyCredentialUserEntity(
        $this->email,
        $this->webauthnUserHandle,
        $this->prenom . ' ' . $this->nom
    );
}
```

### Étape 4 : Réactiver le bundle

Ouvrez `config/bundles.php` et décommentez :

**Avant :**
```php
// Webauthn\Bundle\WebauthnBundle::class => ['all' => true],
```

**Après :**
```php
Webauthn\Bundle\WebauthnBundle::class => ['all' => true],
```

### Étape 5 : Mettre à jour la base de données

```bash
php bin/console doctrine:schema:update --force
```

Cela ajoutera :
- La colonne `webauthn_user_handle` dans la table `utilisateur`
- La table `passkey` pour stocker les clés publiques

### Étape 6 : Réactiver le bouton Face ID

Ouvrez `templates/admin/auth/login.html.twig` et décommentez le bloc Face ID :

**Cherchez :**
```html
<!-- WebAuthn temporairement désactivé - Activez l'extension Sodium d'abord -->
<!--
<div class="divider">
...
-->
```

**Supprimez les commentaires :**
```html
<div class="divider">
    <span>OU</span>
</div>

<button type="button" id="faceIdLoginBtn" class="btn-login" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
    <i class="fas fa-fingerprint me-2"></i>Se connecter avec Face ID / Touch ID
</button>

<div id="faceIdMessage" class="mt-3"></div>
```

### Étape 7 : Vider le cache

```bash
php bin/console cache:clear
```

### Étape 8 : Redémarrer le serveur

```bash
symfony server:restart
# OU redémarrez manuellement
```

### Étape 9 : Tester

1. Allez sur `http://localhost:8000/admin/login` (utilisez **localhost**, pas 127.0.0.1)
2. Connectez-vous avec email/mot de passe
3. Allez sur `/profile/setup-faceid`
4. Configurez Face ID
5. Testez la connexion avec Face ID

## ✅ Checklist de réactivation

- [ ] Extension Sodium installée et vérifiée
- [ ] Fichiers .disabled renommés
- [ ] PublicKeyCredentialSource.php corrigé (userHandle public)
- [ ] Utilisateur.php restauré (propriété + méthodes)
- [ ] Bundle WebAuthn décommenté dans bundles.php
- [ ] Base de données mise à jour
- [ ] Bouton Face ID décommenté dans login.html.twig
- [ ] Cache vidé
- [ ] Serveur redémarré
- [ ] Test de connexion Face ID réussi

## 📚 Documentation complète

Pour plus de détails, consultez :
- `REACTIVER_WEBAUTHN.md` - Guide détaillé de réactivation
- `WEBAUTHN_SETUP.md` - Guide complet WebAuthn
- `ACTIVER_SODIUM.md` - Installation de Sodium

## ⚠️ Important

Ne réactivez WebAuthn que si :
1. ✅ Sodium est installé et fonctionne
2. ✅ Vous utilisez `localhost` (pas 127.0.0.1)
3. ✅ Vous avez un navigateur compatible (Chrome, Firefox, Safari, Edge)

Sinon, laissez WebAuthn désactivé et utilisez l'authentification normale. 😊
