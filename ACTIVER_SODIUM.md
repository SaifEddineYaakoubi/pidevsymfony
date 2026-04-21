# 🔧 Activer l'extension Sodium sur Windows

## Problème
```
The extension "sodium" is not available. Please install it to use this method
```

## Solution rapide

### Étape 1 : Ouvrir le fichier php.ini

Votre fichier php.ini est situé à :
```
C:\xampp\php\php.ini
```

Ouvrez-le avec un éditeur de texte (Notepad++, VS Code, ou Notepad).

### Étape 2 : Rechercher et décommenter la ligne sodium

1. Appuyez sur `Ctrl+F` pour rechercher
2. Cherchez : `sodium`
3. Vous devriez trouver une ligne comme :
   ```ini
   ;extension=sodium
   ```

4. Supprimez le point-virgule (`;`) au début :
   ```ini
   extension=sodium
   ```

### Étape 3 : Vérifier que libsodium.dll existe

Vérifiez que le fichier existe dans :
```
C:\xampp\php\ext\php_sodium.dll
```

Si le fichier n'existe pas, vous devrez peut-être mettre à jour votre installation PHP.

### Étape 4 : Redémarrer Apache (si vous utilisez XAMPP)

1. Ouvrez le panneau de contrôle XAMPP
2. Arrêtez Apache
3. Redémarrez Apache

### Étape 5 : Vérifier l'installation

Ouvrez un nouveau terminal et exécutez :
```bash
php -m | findstr sodium
```

Vous devriez voir :
```
sodium
```

## Alternative : Modification manuelle rapide

Si vous ne trouvez pas la ligne dans php.ini, ajoutez-la manuellement :

1. Ouvrez `C:\xampp\php\php.ini`
2. Cherchez la section `[PHP]` ou les autres `extension=`
3. Ajoutez cette ligne :
   ```ini
   extension=sodium
   ```
4. Sauvegardez
5. Redémarrez Apache

## Vérification finale

Après avoir activé sodium, testez avec cette commande :

```bash
php -r "echo extension_loaded('sodium') ? 'Sodium OK' : 'Sodium KO';"
```

Résultat attendu : `Sodium OK`

## Si ça ne fonctionne toujours pas

### Option 1 : Vérifier la version de PHP

Sodium est inclus par défaut depuis PHP 7.2. Vérifiez votre version :
```bash
php -v
```

Vous avez PHP 8.1.25, donc sodium devrait être disponible.

### Option 2 : Réinstaller PHP

Si le fichier `php_sodium.dll` n'existe pas dans `C:\xampp\php\ext\`, vous devrez peut-être :
1. Télécharger une version plus récente de XAMPP
2. Ou télécharger PHP manuellement depuis https://windows.php.net/download/

### Option 3 : Utiliser une solution temporaire (non recommandé)

En attendant de résoudre le problème sodium, vous pouvez temporairement désactiver WebAuthn et utiliser uniquement l'authentification par mot de passe.

## Après avoir activé Sodium

Une fois sodium activé, retournez sur votre application :

```bash
# Vider le cache Symfony
php bin/console cache:clear

# Redémarrer le serveur
symfony server:start
# OU
php -S localhost:8000 -t public/
```

Puis testez à nouveau la connexion !
