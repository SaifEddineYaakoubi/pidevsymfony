# 📱 Système de QR Code - Documentation Complète

## Vue d'ensemble

Le système de QR Code permet de générer des codes QR pour les entités **Vente** et **Client**, facilitant le partage et l'accès rapide aux informations via scan mobile.

## 🎯 Fonctionnalités

### 1. Génération de QR Code
- ✅ QR Code pour chaque Vente (données JSON + URL)
- ✅ QR Code pour chaque Client (données JSON + URL)
- ✅ Encodage UTF-8 pour les caractères spéciaux
- ✅ Correction d'erreur élevée (High)
- ✅ Taille et marges configurables
- ✅ Label personnalisé sous le QR code

### 2. Affichage
- ✅ Modal moderne et responsive
- ✅ Animation fluide d'ouverture/fermeture
- ✅ Loader pendant la génération
- ✅ Gestion des erreurs

### 3. Téléchargement
- ✅ Bouton de téléchargement direct
- ✅ Format PNG
- ✅ Nom de fichier personnalisé

## 📦 Installation

```bash
composer require endroid/qr-code
```

Version installée: `^5.1` (compatible PHP 8.1+)

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    ARCHITECTURE                          │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  Controller (VenteController)                           │
│       │                                                  │
│       ├──> Service (QrCodeService)                      │
│       │         │                                        │
│       │         ├──> endroid/qr-code (Builder)          │
│       │         └──> UrlGenerator                       │
│       │                                                  │
│       └──> View (Twig Components)                       │
│                 │                                        │
│                 ├──> _qrcode_modal.html.twig            │
│                 ├──> _qrcode_button.html.twig           │
│                 └──> JavaScript (AJAX)                  │
│                                                          │
└─────────────────────────────────────────────────────────┘
```

## 📁 Fichiers Créés

### Services
- `src/Service/QrCodeService.php` - Service principal de génération

### Controllers
- Routes ajoutées dans `src/Controller/VenteController.php`:
  - `/vente/{idVente}/qrcode` - Génère le QR code (JSON)
  - `/vente/{idVente}/qrcode/download` - Télécharge le QR code
  - `/client/{id_client}/qrcode` - Génère le QR code client (JSON)
  - `/client/{id_client}/qrcode/download` - Télécharge le QR code client

### Templates
- `templates/components/_qrcode_modal.html.twig` - Modal réutilisable
- `templates/components/_qrcode_button.html.twig` - Bouton réutilisable
- `templates/vente/index_with_qrcode.html.twig` - Exemple d'intégration

## 💻 Utilisation

### 1. Dans un Controller

```php
use App\Service\QrCodeService;

public function myAction(QrCodeService $qrCodeService, Vente $vente)
{
    // Générer un QR code en base64
    $qrCodeBase64 = $qrCodeService->generateVenteQrCode($vente);
    
    // Générer avec URL au lieu de JSON
    $qrCodeBase64 = $qrCodeService->generateVenteQrCode($vente, true, true);
    
    // Générer pour un client
    $qrCodeBase64 = $qrCodeService->generateClientQrCode($client);
    
    // QR code personnalisé
    $qrCodeBase64 = $qrCodeService->generateCustomQrCode(
        'https://example.com',
        '/path/to/logo.png',
        'Mon Label',
        400
    );
}
```

### 2. Dans Twig

#### Inclure le Modal (une seule fois par page)
```twig
{% include 'components/_qrcode_modal.html.twig' %}
```

#### Ajouter un Bouton QR Code
```twig
{# Pour une vente #}
{% include 'components/_qrcode_button.html.twig' with {
    'type': 'vente',
    'id': vente.idVente,
    'label': 'Vente #' ~ vente.idVente,
    'btn_class': 'btn btn-info btn-sm'
} %}

{# Pour un client #}
{% include 'components/_qrcode_button.html.twig' with {
    'type': 'client',
    'id': client.id_client,
    'label': client.nom,
    'btn_class': 'btn btn-primary btn-sm',
    'icon_only': true
} %}
```

#### Bouton Personnalisé
```twig
<button 
    onclick="openQrCodeModal('{{ path('app_vente_qrcode', {'idVente': vente.idVente}) }}', 'vente', {{ vente.idVente }}, 'Vente #{{ vente.idVente }}')"
    class="btn btn-info"
>
    <i class="fas fa-qrcode"></i> QR Code
</button>
```

### 3. JavaScript

Les fonctions JavaScript sont incluses dans le modal :

```javascript
// Ouvrir le modal
openQrCodeModal(url, type, id, label);

// Fermer le modal
closeQrCodeModal();
```

## 📊 Données Encodées

### QR Code Vente (JSON)
```json
{
    "type": "vente",
    "id": 1,
    "client": "Ahmed Boussaidi",
    "produit": "Tomate",
    "quantite": 10,
    "montant_total": 25.00,
    "date": "2025-04-21",
    "url": "https://example.com/vente/1"
}
```

### QR Code Client (JSON)
```json
{
    "type": "client",
    "id": 1,
    "nom": "Ahmed Boussaidi",
    "contact": "ahmed@example.com",
    "adresse": "Tunis",
    "badge": "gold",
    "nb_ventes": 5,
    "url": "https://example.com/client/1"
}
```

## 🎨 Personnalisation

### Modifier la Taille du QR Code

Dans `QrCodeService.php`:
```php
->size(300)  // Changer à 400, 500, etc.
->margin(10) // Changer la marge
```

### Ajouter un Logo

```php
$qrCodeService->generateCustomQrCode(
    $data,
    'public/images/logo.png',  // Chemin vers le logo
    'Mon Label',
    300
);
```

### Modifier le Style du Modal

Éditer les styles dans `templates/components/_qrcode_modal.html.twig`:
```css
.qrcode-modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    /* Changer le gradient */
}
```

## 🔗 Routes Disponibles

| Route | Méthode | Description | Retour |
|-------|---------|-------------|--------|
| `/vente/{id}/qrcode` | GET | Génère QR code vente | JSON + base64 |
| `/vente/{id}/qrcode/download` | GET | Télécharge QR code | PNG file |
| `/client/{id}/qrcode` | GET | Génère QR code client | JSON + base64 |
| `/client/{id}/qrcode/download` | GET | Télécharge QR code | PNG file |

## 📱 Scan du QR Code

### Avec URL
Si `withUrl = true`, le QR code contient directement l'URL:
```
https://example.com/vente/1
```
Le scan redirige directement vers la page.

### Avec JSON
Si `withUrl = false`, le QR code contient les données JSON.
Une application mobile peut:
1. Scanner le QR code
2. Parser le JSON
3. Afficher les données
4. Utiliser l'URL incluse dans le JSON

## 🧪 Tests

### Test Manuel

1. **Générer un QR Code:**
   - Aller sur la liste des ventes
   - Cliquer sur le bouton "QR Code"
   - Vérifier que le modal s'ouvre
   - Vérifier que le QR code s'affiche

2. **Scanner le QR Code:**
   - Utiliser une application de scan (Google Lens, etc.)
   - Scanner le QR code affiché
   - Vérifier les données ou la redirection

3. **Télécharger:**
   - Cliquer sur "Télécharger"
   - Vérifier que le fichier PNG est téléchargé
   - Ouvrir le fichier et scanner

### Test Programmatique

```php
// Dans un test Symfony
public function testQrCodeGeneration()
{
    $qrCodeService = static::getContainer()->get(QrCodeService::class);
    $vente = $this->createTestVente();
    
    $qrCode = $qrCodeService->generateVenteQrCode($vente);
    
    $this->assertNotEmpty($qrCode);
    $this->assertStringStartsWith('iVBORw0KGgo', $qrCode); // PNG base64
}
```

## 🚀 Améliorations Futures

### 1. QR Code Dynamique
Créer une page de redirection qui affiche les données:
```php
#[Route('/qr/{type}/{id}', name: 'app_qr_redirect')]
public function qrRedirect(string $type, int $id): Response
{
    // Afficher une page avec les données formatées
}
```

### 2. Statistiques de Scan
Tracker combien de fois un QR code est scanné:
```php
#[ORM\Column(type: 'integer')]
private int $qrCodeScans = 0;
```

### 3. QR Code avec Expiration
Ajouter une date d'expiration au QR code:
```json
{
    "expires_at": "2025-12-31",
    "data": {...}
}
```

### 4. QR Code Coloré
Personnaliser les couleurs:
```php
->foregroundColor(new Color(102, 126, 234))
->backgroundColor(new Color(255, 255, 255))
```

### 5. Batch Export
Exporter tous les QR codes en ZIP:
```php
public function exportAllQrCodes(): Response
{
    $zip = new ZipArchive();
    // Ajouter tous les QR codes
    return $zip;
}
```

### 6. API REST
Créer une API pour les applications mobiles:
```php
#[Route('/api/qrcode/vente/{id}', methods: ['GET'])]
public function apiQrCode(int $id): JsonResponse
{
    return $this->json([
        'qrcode' => $qrCodeBase64,
        'data' => $venteData
    ]);
}
```

## 🐛 Dépannage

### Le QR Code ne s'affiche pas
1. Vérifier que la bibliothèque est installée:
   ```bash
   composer show endroid/qr-code
   ```

2. Vider le cache:
   ```bash
   php bin/console cache:clear
   ```

3. Vérifier les logs:
   ```bash
   tail -f var/log/dev.log
   ```

### Le modal ne s'ouvre pas
1. Vérifier que le modal est inclus:
   ```twig
   {% include 'components/_qrcode_modal.html.twig' %}
   ```

2. Vérifier la console JavaScript (F12)

3. Vérifier que jQuery/Bootstrap sont chargés (si utilisés)

### Erreur 404 sur les routes
1. Vérifier que les routes sont bien ajoutées dans le controller

2. Lister les routes:
   ```bash
   php bin/console debug:router | grep qrcode
   ```

## 📚 Ressources

- [Documentation endroid/qr-code](https://github.com/endroid/qr-code)
- [QR Code Standards](https://www.qrcode.com/en/about/standards.html)
- [Symfony Routing](https://symfony.com/doc/current/routing.html)

---

**Système de QR Code opérationnel et prêt pour la production ! 📱**
