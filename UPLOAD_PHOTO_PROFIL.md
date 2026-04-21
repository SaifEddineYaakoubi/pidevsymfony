# 📸 Upload de photo de profil - Implémenté

## ✅ Ce qui a été ajouté

### 1. Base de données

**Colonne ajoutée** : `profile_picture` (VARCHAR 255)

```sql
ALTER TABLE utilisateur ADD profile_picture VARCHAR(255) DEFAULT NULL;
```

### 2. Entité Utilisateur

**Fichier** : `src/Entity/Utilisateur.php`

```php
#[ORM\Column(type: 'string', length: 255, nullable: true)]
private ?string $profilePicture = null;

public function getProfilePicture(): ?string
{
    return $this->profilePicture;
}

public function setProfilePicture(?string $profilePicture): self
{
    $this->profilePicture = $profilePicture;
    return $this;
}
```

### 3. Contrôleur

**Fichier** : `src/Controller/AdminProfileController.php`

**Fonctionnalités ajoutées** :
- ✅ Réception de l'image en base64
- ✅ Décodage et validation de l'image
- ✅ Redimensionnement automatique (max 500px)
- ✅ Sauvegarde dans `public/uploads/profiles/`
- ✅ Suppression de l'ancienne photo
- ✅ Nom de fichier unique : `profile_{userId}_{timestamp}.jpg`

**Méthode ajoutée** :
```php
private function saveProfilePicture(Utilisateur $user, string $base64Image): string
{
    // Décode l'image base64
    // Crée le dossier si nécessaire
    // Supprime l'ancienne photo
    // Sauvegarde la nouvelle photo
    // Retourne le nom du fichier
}
```

### 4. Interface utilisateur

**Fichier** : `templates/admin/partials/_sidebar.html.twig`

#### Dans le modal de profil :

**Avant** :
```html
<img src="{{ asset('adminlte/dist/img/user2-160x160.jpg') }}" ...>
```

**Après** :
```html
<img id="profilePicturePreview" 
     src="{% if app.user.profilePicture %}
            {{ asset('uploads/profiles/' ~ app.user.profilePicture) }}
          {% else %}
            {{ asset('adminlte/dist/img/user2-160x160.jpg') }}
          {% endif %}" ...>

<!-- Bouton caméra pour changer la photo -->
<label for="profilePictureInput">
    <div class="bg-primary text-white rounded-circle">
        <i class="fas fa-camera"></i>
    </div>
</label>

<input type="file" id="profilePictureInput" accept="image/*" style="display: none;">
<input type="hidden" id="profilePictureData" name="profile_picture_data">
```

#### Dans la sidebar (en haut) :

**Avant** :
```html
<img src="{{ asset('adminlte/dist/img/user2-160x160.jpg') }}" ...>
```

**Après** :
```html
<img src="{% if app.user.profilePicture %}
           {{ asset('uploads/profiles/' ~ app.user.profilePicture) }}
         {% else %}
           {{ asset('adminlte/dist/img/user2-160x160.jpg') }}
         {% endif %}" ...>
```

### 5. JavaScript

**Fonctionnalités** :
- ✅ Sélection de fichier via input file
- ✅ Validation du type (image uniquement)
- ✅ Validation de la taille (max 5 MB)
- ✅ Lecture de l'image avec FileReader
- ✅ Redimensionnement avec Canvas (max 500px)
- ✅ Conversion en base64
- ✅ Prévisualisation immédiate
- ✅ Stockage dans champ caché
- ✅ Envoi au serveur lors de la sauvegarde

**Code** :
```javascript
$('#profilePictureInput').on('change', function(e) {
    const file = e.target.files[0];
    
    // Validation
    if (!file.type.match('image.*')) { ... }
    if (file.size > 5 * 1024 * 1024) { ... }
    
    // Lecture et redimensionnement
    const reader = new FileReader();
    reader.onload = function(event) {
        const img = new Image();
        img.onload = function() {
            // Redimensionner avec Canvas
            const canvas = document.createElement('canvas');
            // ...
            const base64Image = canvas.toDataURL('image/jpeg', 0.8);
            
            // Prévisualisation
            $('#profilePicturePreview').attr('src', base64Image);
            
            // Stockage
            $('#profilePictureData').val(base64Image);
        };
        img.src = event.target.result;
    };
    reader.readAsDataURL(file);
});
```

### 6. Dossier de stockage

**Créé** : `public/uploads/profiles/`

**Permissions** : 0777 (lecture/écriture)

**Structure** :
```
public/
└── uploads/
    └── profiles/
        ├── profile_1_1713648000.jpg
        ├── profile_2_1713648123.jpg
        └── ...
```

## 🎯 Comment ça fonctionne

### Flux complet

```
1. Utilisateur clique sur l'icône caméra
   ↓
2. Sélectionne une image depuis son ordinateur
   ↓
3. JavaScript valide l'image (type + taille)
   ↓
4. Image redimensionnée à max 500px
   ↓
5. Convertie en base64
   ↓
6. Prévisualisation affichée immédiatement
   ↓
7. Utilisateur clique sur "Enregistrer"
   ↓
8. Formulaire envoyé avec l'image en base64
   ↓
9. Contrôleur reçoit les données
   ↓
10. Image décodée et sauvegardée sur le serveur
    ↓
11. Nom du fichier sauvegardé en base de données
    ↓
12. Page rechargée
    ↓
13. Nouvelle photo affichée partout
```

## 🔐 Sécurité

### Validations côté client (JavaScript)

- ✅ Type de fichier : Uniquement images
- ✅ Taille : Maximum 5 MB
- ✅ Redimensionnement : Maximum 500px

### Validations côté serveur (PHP)

- ✅ Décodage base64 sécurisé
- ✅ Vérification de l'utilisateur connecté
- ✅ Nom de fichier unique (évite les collisions)
- ✅ Suppression de l'ancienne photo
- ✅ Gestion des erreurs

### Stockage

- ✅ Dossier dédié : `public/uploads/profiles/`
- ✅ Nom de fichier : `profile_{userId}_{timestamp}.jpg`
- ✅ Format : JPEG (compression 80%)
- ✅ Taille max : 500px (économise l'espace)

## 📊 Exemple de données

### Base de données

```sql
SELECT id_user, nom, prenom, email, profile_picture 
FROM utilisateur 
WHERE id_user = 1;

+----------+--------+--------+------------------+---------------------------+
| id_user  | nom    | prenom | email            | profile_picture           |
+----------+--------+--------+------------------+---------------------------+
| 1        | Dupont | Jean   | jean@example.com | profile_1_1713648000.jpg  |
+----------+--------+--------+------------------+---------------------------+
```

### Fichier sur le serveur

```
public/uploads/profiles/profile_1_1713648000.jpg
```

### URL d'accès

```
http://localhost:8000/uploads/profiles/profile_1_1713648000.jpg
```

## 🎨 Interface utilisateur

### Modal de profil

**Avant** :
- Photo par défaut (user2-160x160.jpg)
- Pas de possibilité de changer

**Après** :
- Photo personnalisée ou par défaut
- Icône caméra en bas à droite
- Clic sur l'icône → Sélection de fichier
- Prévisualisation immédiate
- Message "Cliquez sur l'icône pour changer la photo"

### Sidebar

**Avant** :
- Photo par défaut pour tous les utilisateurs

**Après** :
- Photo personnalisée de chaque utilisateur
- Mise à jour automatique après changement

## 🔧 Configuration

### Taille maximale de l'image

**JavaScript** (ligne ~20) :
```javascript
if (file.size > 5 * 1024 * 1024) { // 5 MB
```

**Modifier** :
```javascript
if (file.size > 10 * 1024 * 1024) { // 10 MB
```

### Dimensions maximales

**JavaScript** (ligne ~30) :
```javascript
const maxSize = 500; // 500px
```

**Modifier** :
```javascript
const maxSize = 800; // 800px
```

### Qualité JPEG

**JavaScript** (ligne ~50) :
```javascript
const base64Image = canvas.toDataURL('image/jpeg', 0.8); // 80%
```

**Modifier** :
```javascript
const base64Image = canvas.toDataURL('image/jpeg', 0.9); // 90%
```

### Dossier de stockage

**PHP** (AdminProfileController.php, ligne ~70) :
```php
$uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles/';
```

**Modifier** :
```php
$uploadDir = $this->getParameter('kernel.project_dir') . '/public/images/users/';
```

## 🐛 Dépannage

### L'image ne s'affiche pas

**Vérifications** :
1. Le dossier `public/uploads/profiles/` existe ?
2. Les permissions sont correctes (0777) ?
3. Le fichier existe dans le dossier ?
4. Le nom du fichier est correct en base de données ?

**Solution** :
```bash
# Vérifier le dossier
ls public/uploads/profiles/

# Vérifier les permissions
chmod 777 public/uploads/profiles/

# Vérifier la base de données
SELECT profile_picture FROM utilisateur WHERE id_user = 1;
```

### Erreur "Image invalide"

**Cause** : Format d'image non supporté ou corrompu

**Solution** :
- Utilisez JPG, PNG ou GIF
- Vérifiez que l'image n'est pas corrompue
- Réessayez avec une autre image

### Erreur "Impossible de sauvegarder l'image"

**Cause** : Permissions insuffisantes

**Solution** :
```bash
chmod 777 public/uploads/profiles/
```

### L'ancienne photo n'est pas supprimée

**Cause** : Fichier verrouillé ou permissions

**Solution** :
- Vérifiez les permissions du dossier
- Vérifiez que le fichier n'est pas utilisé ailleurs

## 📈 Améliorations possibles

### Court terme

1. **Crop d'image**
   - Permettre de recadrer l'image
   - Forcer un format carré

2. **Filtres**
   - Ajouter des filtres (noir et blanc, sépia, etc.)
   - Ajuster la luminosité/contraste

3. **Validation avancée**
   - Vérifier le contenu de l'image (pas de contenu inapproprié)
   - Détecter les visages

### Moyen terme

4. **Galerie**
   - Historique des photos de profil
   - Possibilité de revenir à une ancienne photo

5. **Compression**
   - Compression automatique plus agressive
   - Conversion en WebP

6. **CDN**
   - Stocker les images sur un CDN
   - Améliorer les performances

### Long terme

7. **Avatar par défaut**
   - Générer un avatar avec les initiales
   - Couleur basée sur le nom

8. **Intégration sociale**
   - Importer depuis Facebook/Google
   - Synchroniser avec Gravatar

## ✅ Résumé

### Ce qui fonctionne

✅ **Upload d'image** depuis le modal de profil
✅ **Prévisualisation** immédiate
✅ **Redimensionnement** automatique (500px max)
✅ **Validation** (type + taille)
✅ **Sauvegarde** sur le serveur
✅ **Affichage** dans la sidebar et le profil
✅ **Suppression** de l'ancienne photo
✅ **Sécurité** (validation côté client et serveur)

### Fichiers modifiés

- ✅ `src/Entity/Utilisateur.php`
- ✅ `src/Controller/AdminProfileController.php`
- ✅ `templates/admin/partials/_sidebar.html.twig`

### Base de données

- ✅ Colonne `profile_picture` ajoutée
- ✅ Schéma synchronisé

### Dossier créé

- ✅ `public/uploads/profiles/`

---

**L'upload de photo de profil est maintenant fonctionnel !** 📸

Testez-le en allant dans "Mon Compte" → "Mon Profil" et en cliquant sur l'icône caméra ! 😊
