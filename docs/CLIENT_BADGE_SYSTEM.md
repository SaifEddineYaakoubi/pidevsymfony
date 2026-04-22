# Système de Badges Clients - Documentation

## Vue d'ensemble

Le système de badges clients est une **logique métier avancée** qui classifie automatiquement les clients en fonction du nombre de ventes effectuées. Cette implémentation suit les meilleures pratiques Symfony avec une séparation claire des responsabilités.

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    ARCHITECTURE                              │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Controller (VenteController)                               │
│       │                                                      │
│       ├──> Service (ClientBadgeService)                     │
│       │         │                                            │
│       │         ├──> Entity (Client)                        │
│       │         ├──> Enum (ClientBadge)                     │
│       │         └──> Repository (ClientRepository)          │
│       │                                                      │
│       └──> View (Twig + ClientBadgeExtension)               │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Composants

### 1. Enum: `ClientBadge`
**Fichier:** `src/Enum/ClientBadge.php`

Définit les différents types de badges avec leurs propriétés :
- `GOLD` (🥇) : 3+ ventes
- `SILVER` (🥈) : 2 ventes
- `BRONZE` (🥉) : 1 vente
- `NONE` (⚪) : 0 vente

**Méthodes principales:**
```php
ClientBadge::fromVenteCount(int $count): self
$badge->getLabel(): string
$badge->getIcon(): string
$badge->getCssClass(): string
```

### 2. Service: `ClientBadgeService`
**Fichier:** `src/Service/ClientBadgeService.php`

Service principal contenant toute la logique métier.

**Méthodes:**

#### `calculateBadge(Client $client): ClientBadge`
Calcule le badge d'un client sans le persister.

```php
$badge = $badgeService->calculateBadge($client);
echo $badge->getLabel(); // "Or", "Argent", "Bronze", ou "Aucun"
```

#### `updateClientBadge(Client $client, bool $flush = true): ClientBadge`
Calcule ET persiste le badge en base de données.

```php
$badge = $badgeService->updateClientBadge($client);
// Le badge est automatiquement sauvegardé
```

#### `updateAllClientBadges(): int`
Met à jour les badges de tous les clients (utile pour migration).

```php
$count = $badgeService->updateAllClientBadges();
echo "$count clients mis à jour";
```

#### `getBadgeStatistics(): array`
Retourne les statistiques de distribution des badges.

```php
$stats = $badgeService->getBadgeStatistics();
// ['gold' => 5, 'silver' => 10, 'bronze' => 15, 'none' => 20, 'total' => 50]
```

#### `getTopClients(): array`
Retourne tous les clients avec badge Gold.

```php
$vipClients = $badgeService->getTopClients();
```

### 3. Entity: `Client`
**Fichier:** `src/Entity/Client.php`

Ajout du champ `badge` :

```php
#[ORM\Column(type: "string", length: 20, nullable: true)]
private ?string $badge = null;
```

**Nouvelles méthodes:**
```php
$client->getBadge(): ?string              // Retourne 'gold', 'silver', etc.
$client->setBadge(?string $badge): self   // Définit le badge
$client->getBadgeEnum(): ClientBadge      // Retourne l'enum ClientBadge
```

### 4. Extension Twig: `ClientBadgeExtension`
**Fichier:** `src/Twig/ClientBadgeExtension.php`

Fournit des fonctions et filtres Twig pour afficher les badges.

**Filtres:**
```twig
{{ client|badge_html }}           {# Affiche le badge complet #}
{{ client|badge_html(false) }}    {# Affiche uniquement l'icône #}
```

**Fonctions:**
```twig
{% set badge = client_badge(client) %}
{{ badge.icon }} {{ badge.label }}

{% set stats = badge_stats() %}
Clients Or: {{ stats.gold }}
```

### 5. Commande Console: `UpdateClientBadgesCommand`
**Fichier:** `src/Command/UpdateClientBadgesCommand.php`

Commande pour mettre à jour tous les badges manuellement.

```bash
php bin/console app:update-client-badges
```

Affiche les statistiques après mise à jour.

## Intégration dans le Controller

### VenteController

Le badge est automatiquement mis à jour après chaque vente :

```php
public function new(
    Request $request, 
    EntityManagerInterface $entityManager,
    ClientBadgeService $badgeService
): Response {
    // ... création de la vente ...
    
    if ($form->isSubmitted() && $form->isValid()) {
        $entityManager->persist($vente);
        $entityManager->flush();
        
        // Mise à jour automatique du badge
        $client = $vente->getIdClient();
        if ($client) {
            $badge = $badgeService->updateClientBadge($client);
            $this->addFlash('info', sprintf(
                'Badge du client mis à jour: %s %s',
                $badge->getIcon(),
                $badge->getLabel()
            ));
        }
    }
}
```

## Utilisation dans les Templates Twig

### Méthode 1: Filtre badge_html
```twig
<td>
    {{ client.nom }}
    {{ client|badge_html }}
</td>
```

### Méthode 2: Partial réutilisable
```twig
{% include 'client/_badge_display.html.twig' with {'client': client} %}
```

### Méthode 3: Accès direct à l'enum
```twig
{% set badge = client_badge(client) %}
<span class="{{ badge.cssClass }}">
    {{ badge.icon }} {{ badge.label }}
</span>
```

### Méthode 4: Conditions
```twig
{% set badge = client_badge(client) %}
{% if badge.value == 'gold' %}
    <div class="alert alert-warning">
        <i class="fas fa-crown"></i> Client VIP!
    </div>
{% endif %}
```

### Afficher les statistiques
```twig
{% set stats = badge_stats() %}
<div class="row">
    <div class="col">
        <h3>{{ stats.gold }}</h3>
        <p>Clients Or</p>
    </div>
    <div class="col">
        <h3>{{ stats.silver }}</h3>
        <p>Clients Argent</p>
    </div>
</div>
```

## Migration de la Base de Données

Le champ `badge` a été ajouté à la table `client` :

```sql
ALTER TABLE client ADD badge VARCHAR(20) DEFAULT NULL;
```

Pour initialiser les badges existants :
```bash
php bin/console app:update-client-badges
```

## Tests

### Test manuel
1. Créer un client
2. Créer 1 vente → Badge Bronze
3. Créer 2ème vente → Badge Silver
4. Créer 3ème vente → Badge Gold

### Vérifier en base
```sql
SELECT nom, badge, 
       (SELECT COUNT(*) FROM vente WHERE id_client = client.id_client) as nb_ventes
FROM client;
```

## Améliorations Futures

### 1. Événements Symfony
Utiliser un EventSubscriber pour mettre à jour automatiquement les badges :

```php
class VenteSubscriber implements EventSubscriberInterface
{
    public function onVenteCreated(VenteCreatedEvent $event): void
    {
        $client = $event->getVente()->getIdClient();
        $this->badgeService->updateClientBadge($client);
    }
}
```

### 2. Cache
Mettre en cache les statistiques de badges :

```php
#[Cache(expires: 3600)]
public function getBadgeStatistics(): array
```

### 3. Notifications
Envoyer un email quand un client atteint un nouveau badge :

```php
if ($oldBadge !== $newBadge) {
    $this->mailer->send(new BadgeUpgradeEmail($client, $newBadge));
}
```

### 4. Historique
Créer une table `badge_history` pour suivre l'évolution :

```php
#[ORM\Entity]
class BadgeHistory
{
    private Client $client;
    private ClientBadge $badge;
    private \DateTimeInterface $achievedAt;
}
```

### 5. Récompenses
Associer des avantages aux badges :

```php
enum ClientBadge
{
    public function getDiscount(): float
    {
        return match($this) {
            self::GOLD => 0.15,    // 15% de réduction
            self::SILVER => 0.10,  // 10% de réduction
            self::BRONZE => 0.05,  // 5% de réduction
            self::NONE => 0.0,
        };
    }
}
```

## Bonnes Pratiques Respectées

✅ **Séparation des responsabilités** : Service dédié pour la logique métier  
✅ **Type-safety** : Utilisation d'Enum PHP 8.1+  
✅ **Injection de dépendances** : Service injecté via le constructeur  
✅ **Logging** : Traçabilité des mises à jour de badges  
✅ **Réutilisabilité** : Extension Twig pour usage dans tous les templates  
✅ **Testabilité** : Service facilement mockable pour les tests  
✅ **Documentation** : Code commenté et documentation complète  
✅ **Commande CLI** : Maintenance et migration facilitées  

## Conclusion

Ce système de badges est une implémentation complète et professionnelle d'une logique métier avancée dans Symfony. Il est extensible, maintenable et suit toutes les meilleures pratiques du framework.
