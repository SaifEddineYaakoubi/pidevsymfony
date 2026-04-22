# 💰 Système de Réductions Basé sur les Badges

## Vue d'ensemble

Le système de réductions applique automatiquement des remises sur les achats en fonction du badge du client. Plus le badge est élevé, plus la réduction est importante.

## Barème des Réductions

| Badge | Icône | Ventes Requises | Réduction | Avantages Supplémentaires |
|-------|-------|-----------------|-----------|---------------------------|
| **Gold** | 🥇 | 3+ | **15%** | Livraison gratuite + Support prioritaire |
| **Silver** | 🥈 | 2 | **10%** | Notifications promotions |
| **Bronze** | 🥉 | 1 | **5%** | Newsletter mensuelle |
| **None** | ⚪ | 0 | **0%** | Aucun |

## Exemples de Calcul

### Client Gold (15% de réduction)
```
Prix de base:     100.00 TND
Réduction (15%):  -15.00 TND
─────────────────────────────
Prix final:        85.00 TND
Économie:          15.00 TND
```

### Client Silver (10% de réduction)
```
Prix de base:     100.00 TND
Réduction (10%):  -10.00 TND
─────────────────────────────
Prix final:        90.00 TND
Économie:          10.00 TND
```

### Client Bronze (5% de réduction)
```
Prix de base:     100.00 TND
Réduction (5%):    -5.00 TND
─────────────────────────────
Prix final:        95.00 TND
Économie:           5.00 TND
```

## Utilisation dans le Code

### 1. Dans un Controller

```php
use App\Service\PricingService;

public function myAction(PricingService $pricingService, Client $client)
{
    // Calculer le prix avec réduction
    $pricing = $pricingService->calculatePriceWithDiscount(100.0, $client);
    
    echo $pricing['base_price'];          // 100.0
    echo $pricing['discount_percentage']; // 15.0 (pour Gold)
    echo $pricing['discount_amount'];     // 15.0
    echo $pricing['final_price'];         // 85.0
    echo $pricing['savings'];             // 15.0
    
    // Calculer le prix d'un produit
    $pricing = $pricingService->calculateProductPrice($produit, $quantity, $client);
    
    // Calculer le total d'une commande
    $items = [
        ['produit' => $produit1, 'quantity' => 2],
        ['produit' => $produit2, 'quantity' => 3],
    ];
    $orderTotal = $pricingService->calculateOrderTotal($items, $client);
}
```

### 2. Dans l'Enum ClientBadge

```php
use App\Enum\ClientBadge;

$badge = ClientBadge::GOLD;

// Obtenir le pourcentage de réduction
$discount = $badge->getDiscountPercentage(); // 15.0

// Appliquer la réduction
$finalPrice = $badge->applyDiscount(100.0); // 85.0

// Calculer le montant de la réduction
$discountAmount = $badge->calculateDiscountAmount(100.0); // 15.0

// Obtenir la description des avantages
$benefits = $badge->getBenefitDescription();
// "Réduction de 15% sur tous les achats + Livraison gratuite"
```

### 3. Dans Twig

#### Afficher le pourcentage de réduction
```twig
{% set discount = client_discount(client) %}
<p>Votre réduction: {{ discount }}%</p>
```

#### Calculer et afficher un prix avec réduction
```twig
{% set pricing = price_with_discount(100, client) %}

<div>
    Prix original: {{ pricing.original }} TND
    Réduction: -{{ pricing.discount_percentage }}%
    Prix final: {{ pricing.final }} TND
    Économie: {{ pricing.discount_amount }} TND
</div>
```

#### Utiliser le composant de prix
```twig
{% include 'components/_price_with_discount.html.twig' with {
    'price': 100,
    'client': client,
    'show_details': true
} %}
```

#### Affichage conditionnel
```twig
{% set badge = client_badge(client) %}
{% if badge.discountPercentage > 0 %}
    <div class="alert alert-success">
        🎉 Vous bénéficiez de {{ badge.discountPercentage }}% de réduction !
    </div>
{% endif %}
```

## Intégration dans VenteController

Le système est automatiquement intégré lors de la création d'une vente :

```php
// Dans VenteController::new()
if ($form->isSubmitted() && $form->isValid()) {
    // Calculer le prix avec réduction
    $pricing = $pricingService->calculateProductPrice($produit, $quantite, $client);
    
    // Appliquer le prix avec réduction
    $vente->setMontantTotal($pricing['final_price']);
    
    // Sauvegarder
    $entityManager->persist($vente);
    $entityManager->flush();
    
    // Afficher un message avec les détails de la réduction
    if ($pricing['discount_percentage'] > 0) {
        $this->addFlash('info', sprintf(
            'Réduction appliquée: -%s%% (Économie: %s TND)',
            $pricing['discount_percentage'],
            number_format($pricing['discount_amount'], 2)
        ));
    }
}
```

## Workflow Complet

```
1. Client sélectionné dans le formulaire de vente
         ↓
2. Badge du client calculé (Gold, Silver, Bronze, None)
         ↓
3. Pourcentage de réduction déterminé (15%, 10%, 5%, 0%)
         ↓
4. Prix de base calculé (prix unitaire × quantité)
         ↓
5. Réduction appliquée au prix de base
         ↓
6. Prix final enregistré dans la vente
         ↓
7. Badge du client mis à jour (si nécessaire)
         ↓
8. Message de confirmation avec détails de la réduction
```

## Exemples Réels

### Exemple 1: Client Gold achète 10kg de tomates à 2.50 TND/kg

```
Prix unitaire:    2.50 TND
Quantité:         10 kg
Prix de base:     25.00 TND
Badge:            🥇 Gold
Réduction (15%):  -3.75 TND
─────────────────────────────
Prix final:       21.25 TND
Économie:          3.75 TND
```

### Exemple 2: Client Silver achète 5kg de carottes à 1.80 TND/kg

```
Prix unitaire:    1.80 TND
Quantité:         5 kg
Prix de base:     9.00 TND
Badge:            🥈 Silver
Réduction (10%):  -0.90 TND
─────────────────────────────
Prix final:       8.10 TND
Économie:         0.90 TND
```

### Exemple 3: Nouveau client achète 3kg d'oignons à 1.00 TND/kg

```
Prix unitaire:    1.00 TND
Quantité:         3 kg
Prix de base:     3.00 TND
Badge:            ⚪ None
Réduction (0%):   0.00 TND
─────────────────────────────
Prix final:       3.00 TND
Économie:         0.00 TND
```

## Avantages du Système

### Pour les Clients
- ✅ Réductions automatiques sur tous les achats
- ✅ Motivation à acheter plus pour obtenir un meilleur badge
- ✅ Transparence totale sur les économies réalisées
- ✅ Avantages supplémentaires (livraison gratuite pour Gold)

### Pour l'Entreprise
- ✅ Fidélisation des clients
- ✅ Augmentation du panier moyen
- ✅ Encouragement aux achats répétés
- ✅ Différenciation concurrentielle

## Configuration

Les pourcentages de réduction sont définis dans `src/Enum/ClientBadge.php` :

```php
public function getDiscountPercentage(): float
{
    return match($this) {
        self::GOLD => 15.0,    // Modifiable
        self::SILVER => 10.0,  // Modifiable
        self::BRONZE => 5.0,   // Modifiable
        self::NONE => 0.0,
    };
}
```

Pour modifier les réductions, il suffit de changer ces valeurs.

## Tests

### Test manuel
1. Créer un client
2. Créer une vente pour ce client
3. Vérifier que le prix final inclut la réduction
4. Créer une 2ème vente → Vérifier la nouvelle réduction
5. Créer une 3ème vente → Vérifier la réduction Gold

### Vérification en base
```sql
SELECT 
    c.nom,
    c.badge,
    v.montant_total,
    p.prix_unitaire,
    v.quantite,
    (p.prix_unitaire * v.quantite) as prix_sans_reduction,
    ((p.prix_unitaire * v.quantite) - v.montant_total) as reduction_appliquee
FROM vente v
JOIN client c ON v.id_client = c.id_client
JOIN produit p ON v.id_produit = p.id_produit
ORDER BY v.date_vente DESC;
```

## Améliorations Futures

1. **Réductions cumulatives** - Combiner badge + promotions
2. **Réductions par catégorie** - Différentes réductions selon le produit
3. **Réductions temporaires** - Happy hours, soldes
4. **Points de fidélité** - Système de points en plus des badges
5. **Récompenses** - Cadeaux pour les clients Gold
6. **Historique des économies** - Tableau de bord des économies totales
7. **Notifications** - Alertes quand proche d'un nouveau badge

## Support

Pour toute question sur le système de réductions :
- Consulter `src/Service/PricingService.php`
- Consulter `src/Enum/ClientBadge.php`
- Voir les exemples dans `templates/client/badge_benefits.html.twig`

---

**Système de réductions opérationnel et prêt à l'emploi ! 💰**
