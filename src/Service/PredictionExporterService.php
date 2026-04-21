<?php

namespace App\Service;

use App\Entity\Recolte;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Service pour exporter les prédictions IA d'une récolte en CSV
 */
class PredictionExporterService
{
    private HuggingFaceService $huggingFaceService;

    public function __construct(HuggingFaceService $huggingFaceService)
    {
        $this->huggingFaceService = $huggingFaceService;
    }

    /**
     * Génère un fichier CSV avec les prédictions IA pour une récolte
     */
    public function generatePredictionCsv(Recolte $recolte): Response
    {
        try {
            // Log pour debug
            error_log("Début génération CSV pour récolte ID: " . $recolte->getIdRecolte());

            // Récupérer les données de prédiction
            $surface = $recolte->getIdCulture()?->getIdParcelle()?->getSuperficie() ?? 10;
            error_log("Surface récupérée: " . $surface . " (culture: " . ($recolte->getIdCulture() ? $recolte->getIdCulture()->getIdCulture() : 'null') . ", parcelle: " . ($recolte->getIdCulture()?->getIdParcelle() ? $recolte->getIdCulture()->getIdParcelle()->getIdParcelle() : 'null') . ")");

            $predictionData = $this->huggingFaceService->predict([
                'surface' => $surface,
                'quantite' => $recolte->getQuantite(),
                'typeCulture' => $recolte->getTypeCulture(),
            ]);

            error_log("Données de prédiction récupérées: " . json_encode($predictionData));

            // Créer le writer CSV
            $csv = Writer::createFromString('');
            $csv->setDelimiter(';');
            $csv->setEnclosure('"');
            $csv->setEscape('\\');

            // En-têtes du CSV
            $headers = [
                'Champ',
                'Valeur',
                'Description'
            ];
            $csv->insertOne($headers);

            // Données de la récolte
            $csv->insertOne(['ID Récolte', $recolte->getIdRecolte(), 'Identifiant unique de la récolte']);
            $csv->insertOne(['Quantité', $recolte->getQuantite() . ' kg', 'Quantité récoltée']);
            $csv->insertOne(['Type de Culture', $recolte->getTypeCulture(), 'Type de culture cultivée']);
            $csv->insertOne(['Localisation', $recolte->getLocalisation(), 'Emplacement de la récolte']);
            $csv->insertOne(['Surface', $surface . ' m²', 'Surface cultivée']);
            $csv->insertOne(['Date de Récolte', $recolte->getDateRecolte() ? $recolte->getDateRecolte()->format('d/m/Y') : 'Non définie', 'Date de la récolte']);

            // Séparateur
            $csv->insertOne(['', '', '']);
            $csv->insertOne(['=== PRÉDICTIONS IA ===', '', '']);

            // Prédictions IA
            if (!isset($predictionData['error'])) {
                // Utiliser des getters sécurisés avec des valeurs par défaut
                $csv->insertOne(['Rendement Prédit', ($predictionData['predictionRendement'] ?? 'N/A') . ' unités/ha', 'Estimation du rendement futur']);
                $csv->insertOne(['Score Qualité', ($predictionData['scoreQualite'] ?? 'N/A') . '/10', 'Évaluation de la qualité sur 10']);

                // Irrigation
                if (isset($predictionData['propositionIrrigation']) && is_array($predictionData['propositionIrrigation'])) {
                    $csv->insertOne(['Irrigation - Statut', $predictionData['propositionIrrigation']['statut'] ?? 'N/A', 'État actuel de l\'irrigation']);
                    $csv->insertOne(['Irrigation - Besoins', ($predictionData['propositionIrrigation']['besoins_mm'] ?? 'N/A') . ' mm', 'Besoins en eau']);
                    $csv->insertOne(['Irrigation - Action', $predictionData['propositionIrrigation']['action'] ?? 'N/A', 'Actions recommandées']);
                }

                // Engrais
                if (isset($predictionData['recommandationEngrais']) && is_array($predictionData['recommandationEngrais'])) {
                    $csv->insertOne(['Engrais - Type', $predictionData['recommandationEngrais']['type_principal'] ?? 'N/A', 'Type d\'engrais recommandé']);
                    $csv->insertOne(['Engrais - Dosage', ($predictionData['recommandationEngrais']['dosage_kg_ha'] ?? 'N/A') . ' kg/ha', 'Quantité recommandée']);
                    $csv->insertOne(['Engrais - Période', $predictionData['recommandationEngrais']['periode'] ?? 'N/A', 'Période d\'application']);
                    $csv->insertOne(['Engrais - Recommandation', $predictionData['recommandationEngrais']['recommandation'] ?? 'N/A', 'Détails de l\'application']);
                }

                // Risques maladie
                if (isset($predictionData['risqueMaladie']) && is_array($predictionData['risqueMaladie'])) {
                    $csv->insertOne(['Risque Maladie - Niveau', $predictionData['risqueMaladie']['niveau_risque'] ?? 'N/A', 'Niveau de risque détecté']);
                    $maladies = isset($predictionData['risqueMaladie']['maladies_potentielles']) && is_array($predictionData['risqueMaladie']['maladies_potentielles'])
                        ? implode(', ', $predictionData['risqueMaladie']['maladies_potentielles'])
                        : 'N/A';
                    $csv->insertOne(['Risque Maladie - Maladies', $maladies, 'Maladies potentielles']);
                    $csv->insertOne(['Risque Maladie - Prévention', $predictionData['risqueMaladie']['prevenance'] ?? 'N/A', 'Mesures préventives']);
                }

                // Époque de récolte
                if (isset($predictionData['epoqueRecolte']) && is_array($predictionData['epoqueRecolte'])) {
                    $csv->insertOne(['Récolte - Période', $predictionData['epoqueRecolte']['periode'] ?? 'N/A', 'Période optimale']);
                    $csv->insertOne(['Récolte - Jours après floraison', ($predictionData['epoqueRecolte']['jours_apres_floraison'] ?? 'N/A') . ' jours', 'Délai après floraison']);
                    $csv->insertOne(['Récolte - Humidité grain', $predictionData['epoqueRecolte']['humidite_grain'] ?? 'N/A', 'Humidité optimale du grain']);
                }

                // Potentiel de rendement
                if (isset($predictionData['potentielRendement']) && is_array($predictionData['potentielRendement'])) {
                    $csv->insertOne(['Potentiel - Actuel', ($predictionData['potentielRendement']['rendement_actuel_qx_ha'] ?? 'N/A') . ' qx/ha', 'Rendement actuel']);
                    $csv->insertOne(['Potentiel - Normal', ($predictionData['potentielRendement']['rendement_normal_qx_ha'] ?? 'N/A') . ' qx/ha', 'Rendement normal attendu']);
                    $csv->insertOne(['Potentiel - Pourcentage', $predictionData['potentielRendement']['pourcentage_potentiel'] ?? 'N/A', 'Pourcentage du potentiel exploité']);
                    $csv->insertOne(['Potentiel - Statut', $predictionData['potentielRendement']['statut'] ?? 'N/A', 'Évaluation du potentiel']);
                }

                // Conseil d'optimisation
                $csv->insertOne(['Conseil IA', $predictionData['conseilOptimisation'] ?? 'N/A', 'Recommandation personnalisée']);
            } else {
                $csv->insertOne(['Erreur', $predictionData['error'] ?? 'Erreur inconnue', 'Erreur lors de la génération des prédictions']);
            }

            // Timestamp
            $csv->insertOne(['', '', '']);
            $csv->insertOne(['Généré le', date('d/m/Y H:i:s'), 'Date et heure de génération']);

            $csvContent = $csv->toString();
            error_log("Contenu CSV généré, longueur: " . strlen($csvContent));

            // Créer la réponse
            $response = new Response($csvContent);
            $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
            $response->headers->set('Content-Disposition', 'attachment; filename="prediction_recolte_' . $recolte->getIdRecolte() . '_' . date('Y-m-d_H-i-s') . '.csv"');
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');

            error_log("Réponse CSV créée avec succès");
            return $response;

        } catch (\Exception $e) {
            error_log("Erreur dans generatePredictionCsv: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());

            // Retourner une réponse d'erreur
            $response = new Response('Erreur lors de la génération du CSV: ' . $e->getMessage());
            $response->headers->set('Content-Type', 'text/plain');
            $response->setStatusCode(500);
            return $response;
        }
    }
}
