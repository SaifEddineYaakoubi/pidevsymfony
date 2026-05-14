<?php

namespace App\Service;

use App\Entity\Culture;
use App\Entity\Parcelle;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Service métier pour la gestion des cultures
 * 
 * Responsabilités:
 * - Valider les règles métier spécifiques aux cultures
 * - Calculer l'état de croissance automatiquement
 * - Gérer la création et modification de cultures
 */
class CultureManager
{
    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    /**
     * Valide une culture selon les règles métier
     * 
     * @param Culture $culture La culture à valider
     * @return ConstraintViolationListInterface Les violations trouvées
     */
    public function validate(Culture $culture): ConstraintViolationListInterface
    {
        return $this->validator->validate($culture);
    }

    /**
     * Crée une nouvelle culture avec validation
     * 
     * @param string $typeCulture Le type de culture
     * @param \DateTimeInterface $datePlantation La date de plantation
     * @param \DateTimeInterface $dateRecoltePrevue La date de récolte prévue
     * @param Parcelle $parcelle La parcelle associée
     * 
     * @return Culture La culture créée
     * @throws \InvalidArgumentException Si les données sont invalides
     */
    public function createCulture(
        string $typeCulture,
        \DateTimeInterface $datePlantation,
        \DateTimeInterface $dateRecoltePrevue,
        Parcelle $parcelle
    ): Culture {
        // Valider que la date de récolte est après la date de plantation
        if ($dateRecoltePrevue <= $datePlantation) {
            throw new \InvalidArgumentException(
                'La date de récolte prévue doit être supérieure à la date de plantation.'
            );
        }

        $culture = new Culture();
        $culture->setType_culture($typeCulture);
        $culture->setDate_plantation($datePlantation);
        $culture->setDate_recolte_prevue($dateRecoltePrevue);
        $culture->setParcelle($parcelle);

        // Calculer l'état de croissance automatiquement
        $culture->updateEtatCroissanceAuto();

        // Valider la culture créée
        $violations = $this->validate($culture);
        if ($violations->count() > 0) {
            throw new \InvalidArgumentException(
                'La culture créée ne respecte pas les contraintes de validation: ' . 
                (string) $violations[0]->getMessage()
            );
        }

        return $culture;
    }

    /**
     * Calcule l'état de croissance basé sur les dates
     * 
     * @param Culture $culture La culture à mettre à jour
     * @return string L'état de croissance calculé
     */
    public function calculateGrowthState(Culture $culture): string
    {
        $culture->updateEtatCroissanceAuto();
        return $culture->getEtat_croissance();
    }

    /**
     * Vérifie si une culture est en retard par rapport à son calendrier
     * 
     * @param Culture $culture La culture à vérifier
     * @return bool True si la culture est en retard
     */
    public function isDelayed(Culture $culture): bool
    {
        $now = new \DateTime();
        $datePlantation = $culture->getDate_plantation();
        $dateRecoltePrevue = $culture->getDate_recolte_prevue();

        if (!$datePlantation || !$dateRecoltePrevue) {
            return false;
        }

        // Si on est après la date de récolte prévue et l'état n'est pas 'maturite'
        if ($now > $dateRecoltePrevue && $culture->getEtat_croissance() !== 'maturite') {
            return true;
        }

        return false;
    }

    /**
     * Obtient le pourcentage de progression de la culture
     * 
     * @param Culture $culture La culture à analyser
     * @return float Le pourcentage de progression (0-100)
     */
    public function getProgressPercentage(Culture $culture): float
    {
        $datePlantation = $culture->getDate_plantation();
        $dateRecoltePrevue = $culture->getDate_recolte_prevue();

        if (!$datePlantation || !$dateRecoltePrevue) {
            return 0.0;
        }

        $now = new \DateTime();

        // Si la plantation n'a pas commencé
        if ($now < $datePlantation) {
            return 0.0;
        }

        // Si la récolte est terminée
        if ($now >= $dateRecoltePrevue) {
            return 100.0;
        }

        $totalDays = (int) $datePlantation->diff($dateRecoltePrevue)->format('%r%a');
        $daysElapsed = (int) $datePlantation->diff($now)->format('%r%a');

        if ($totalDays <= 0) {
            return 100.0;
        }

        return ($daysElapsed / $totalDays) * 100;
    }

    /**
     * Valide que les dates sont cohérentes
     * 
     * @param \DateTimeInterface $datePlantation La date de plantation
     * @param \DateTimeInterface $dateRecoltePrevue La date de récolte prévue
     * 
     * @return bool True si les dates sont valides
     * @throws \InvalidArgumentException Si les dates ne sont pas valides
     */
    public function validateDates(
        \DateTimeInterface $datePlantation,
        \DateTimeInterface $dateRecoltePrevue
    ): bool {
        if ($dateRecoltePrevue <= $datePlantation) {
            throw new \InvalidArgumentException(
                'La date de récolte prévue doit être strictement supérieure à la date de plantation.'
            );
        }

        return true;
    }
}
