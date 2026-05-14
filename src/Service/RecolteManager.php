<?php

namespace App\Service;

use App\Entity\Recolte;
use App\Entity\Culture;
use App\Entity\Utilisateur;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Service métier pour la gestion des récoltes
 * 
 * Responsabilités:
 * - Valider les règles métier spécifiques aux récoltes
 * - Gérer la création et modification de récoltes
 * - Calculer les statistiques de récolte
 */
class RecolteManager
{
    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    /**
     * Valide une récolte selon les règles métier
     * 
     * @param Recolte $recolte La récolte à valider
     * @return ConstraintViolationListInterface Les violations trouvées
     */
    public function validate(Recolte $recolte): ConstraintViolationListInterface
    {
        return $this->validator->validate($recolte);
    }

    /**
     * Crée une nouvelle récolte avec validation
     * 
     * @param float $quantite La quantité récoltée
     * @param \DateTimeInterface $dateRecolte La date de récolte
     * @param string $qualite La qualité de la récolte
     * @param string $typeCulture Le type de culture
     * @param string $localisation La localisation
     * @param Utilisateur $utilisateur L'utilisateur responsable
     * @param Culture|null $culture La culture associée (optionnel)
     * 
     * @return Recolte La récolte créée
     * @throws \InvalidArgumentException Si les données sont invalides
     */
    public function createRecolte(
        float $quantite,
        \DateTimeInterface $dateRecolte,
        string $qualite,
        string $typeCulture,
        string $localisation,
        Utilisateur $utilisateur,
        ?Culture $culture = null
    ): Recolte {
        // Valider que la quantité est positive
        if ($quantite <= 0) {
            throw new \InvalidArgumentException(
                'La quantité doit être strictement supérieure à 0.'
            );
        }

        // Valider que la date n'est pas dans le futur
        $now = new \DateTime();
        if ($dateRecolte > $now) {
            throw new \InvalidArgumentException(
                'La date de récolte ne peut pas être dans le futur.'
            );
        }

        $recolte = new Recolte();
        $recolte->setQuantite($quantite);
        $recolte->setDate_recolte($dateRecolte);
        $recolte->setQualite($qualite);
        $recolte->setType_culture($typeCulture);
        $recolte->setLocalisation($localisation);
        $recolte->setUtilisateur($utilisateur);
        
        if ($culture !== null) {
            $recolte->setId_culture($culture);
        }

        // Valider la récolte créée
        $violations = $this->validate($recolte);
        if ($violations->count() > 0) {
            throw new \InvalidArgumentException(
                'La récolte créée ne respecte pas les contraintes de validation: ' . 
                (string) $violations[0]->getMessage()
            );
        }

        return $recolte;
    }

    /**
     * Valide que la quantité est positive
     * 
     * @param float $quantite La quantité à valider
     * @return bool True si la quantité est valide
     * @throws \InvalidArgumentException Si la quantité n'est pas valide
     */
    public function validateQuantite(float $quantite): bool
    {
        if ($quantite <= 0) {
            throw new \InvalidArgumentException(
                'La quantité doit être strictement supérieure à 0.'
            );
        }

        return true;
    }

    /**
     * Valide que la date de récolte n'est pas dans le futur
     * 
     * @param \DateTimeInterface $dateRecolte La date à valider
     * @return bool True si la date est valide
     * @throws \InvalidArgumentException Si la date n'est pas valide
     */
    public function validateDateRecolte(\DateTimeInterface $dateRecolte): bool
    {
        $now = new \DateTime();
        if ($dateRecolte > $now) {
            throw new \InvalidArgumentException(
                'La date de récolte ne peut pas être dans le futur.'
            );
        }

        return true;
    }

    /**
     * Valide que la qualité est l'une des valeurs acceptées
     * 
     * @param string $qualite La qualité à valider
     * @return bool True si la qualité est valide
     * @throws \InvalidArgumentException Si la qualité n'est pas valide
     */
    public function validateQualite(string $qualite): bool
    {
        $validQualities = ['excellente', 'bonne', 'moyenne', 'mauvaise'];
        
        if (!in_array($qualite, $validQualities, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'La qualité doit être l\'une des valeurs suivantes: %s',
                    implode(', ', $validQualities)
                )
            );
        }

        return true;
    }

    /**
     * Calcule le rendement moyen pour une récolte
     * 
     * @param Recolte $recolte La récolte à analyser
     * @return float Le rendement (quantité / superficie si culture disponible)
     */
    public function calculateYield(Recolte $recolte): float
    {
        $culture = $recolte->getId_culture();
        
        if ($culture === null) {
            return $recolte->getQuantite();
        }

        $parcelle = $culture->getId_parcelle();
        if ($parcelle === null) {
            return $recolte->getQuantite();
        }

        $superficie = $parcelle->getSuperficie();
        if ($superficie <= 0) {
            return $recolte->getQuantite();
        }

        return $recolte->getQuantite() / $superficie;
    }

    /**
     * Obtient la qualité en tant que label lisible
     * 
     * @param string $qualite La qualité
     * @return string Le label lisible
     */
    public function getQualiteLabel(string $qualite): string
    {
        $labels = [
            'excellente' => 'Excellente',
            'bonne' => 'Bonne',
            'moyenne' => 'Moyenne',
            'mauvaise' => 'Mauvaise',
        ];

        return $labels[$qualite] ?? $qualite;
    }

    /**
     * Vérifie si une récolte est de bonne qualité
     * 
     * @param Recolte $recolte La récolte à vérifier
     * @return bool True si la qualité est 'bonne' ou 'excellente'
     */
    public function isGoodQuality(Recolte $recolte): bool
    {
        return in_array($recolte->getQualite(), ['bonne', 'excellente'], true);
    }
}
