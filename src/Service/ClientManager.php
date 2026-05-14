<?php

namespace App\Service;

use App\Entity\Client;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Service métier pour la gestion des clients
 * 
 * Responsabilités:
 * - Valider les règles métier spécifiques aux clients
 * - Gérer la création et modification de clients
 * - Gérer les badges clients
 * - Analyser les informations de contact
 */
class ClientManager
{
    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    /**
     * Valide un client selon les règles métier
     * 
     * @param Client $client Le client à valider
     * @return ConstraintViolationListInterface Les violations trouvées
     */
    public function validate(Client $client): ConstraintViolationListInterface
    {
        return $this->validator->validate($client);
    }

    /**
     * Crée un nouveau client avec validation
     * 
     * @param string $nom Le nom du client
     * @param string $contact L'email ou le téléphone
     * @param string $adresse L'adresse
     * @param int|null $idUser L'ID utilisateur (optionnel)
     * @param string|null $badge Le badge (optionnel)
     * 
     * @return Client Le client créé
     * @throws \InvalidArgumentException Si les données sont invalides
     */
    public function createClient(
        string $nom,
        string $contact,
        string $adresse,
        ?int $idUser = null,
        ?string $badge = null
    ): Client {
        // Valider le contact
        if (!$this->isValidContact($contact)) {
            throw new \InvalidArgumentException(
                'Le contact doit être une adresse email valide ou un numéro de téléphone valide.'
            );
        }

        $client = new Client();
        $client->setNom($nom);
        $client->setContact($contact);
        $client->setAdresse($adresse);
        
        if ($idUser !== null) {
            $client->setId_user($idUser);
        }
        
        if ($badge !== null) {
            $client->setBadge($badge);
        }

        // Valider le client créé
        $violations = $this->validate($client);
        if ($violations->count() > 0) {
            throw new \InvalidArgumentException(
                'Le client créé ne respecte pas les contraintes de validation: ' . 
                (string) $violations[0]->getMessage()
            );
        }

        return $client;
    }

    /**
     * Valide que le contact est un email ou un téléphone
     * 
     * @param string $contact Le contact à valider
     * @return bool True si le contact est valide
     */
    public function isValidContact(string $contact): bool
    {
        // Vérifier si c'est un email
        if (filter_var($contact, FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        // Vérifier si c'est un téléphone (au minimum 8 chiffres)
        if (preg_match('/^[0-9+]{1,3}[0-9]{8,}$/', $contact)) {
            return true;
        }

        return false;
    }

    /**
     * Vérifie si le contact est un email
     * 
     * @param string $contact Le contact
     * @return bool True si c'est un email
     */
    public function isEmail(string $contact): bool
    {
        return filter_var($contact, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Vérifie si le contact est un téléphone
     * 
     * @param string $contact Le contact
     * @return bool True si c'est un téléphone
     */
    public function isPhoneNumber(string $contact): bool
    {
        return preg_match('/^[0-9+]{1,3}[0-9]{8,}$/', $contact) === 1;
    }

    /**
     * Obtient le type de contact
     * 
     * @param string $contact Le contact
     * @return string 'email', 'phone', ou 'unknown'
     */
    public function getContactType(string $contact): string
    {
        if ($this->isEmail($contact)) {
            return 'email';
        }

        if ($this->isPhoneNumber($contact)) {
            return 'phone';
        }

        return 'unknown';
    }

    /**
     * Assigne un badge à un client
     * 
     * @param Client $client Le client
     * @param string $badge Le badge
     * @return Client Le client mis à jour
     */
    public function assignBadge(Client $client, string $badge): Client
    {
        $client->setBadge($badge);
        return $client;
    }

    /**
     * Retire le badge d'un client
     * 
     * @param Client $client Le client
     * @return Client Le client mis à jour
     */
    public function removeBadge(Client $client): Client
    {
        $client->setBadge(null);
        return $client;
    }

    /**
     * Vérifie si un client a un badge
     * 
     * @param Client $client Le client
     * @return bool True si le client a un badge
     */
    public function hasBadge(Client $client): bool
    {
        return $client->getBadge() !== null;
    }

    /**
     * Obtient le label du badge
     * 
     * @param string $badge Le badge
     * @return string Le label
     */
    public function getBadgeLabel(string $badge): string
    {
        $labels = [
            'gold' => 'Or',
            'silver' => 'Argent',
            'bronze' => 'Bronze',
            'none' => 'Aucun',
        ];

        return $labels[$badge] ?? $badge;
    }

    /**
     * Obtient le nom complet du client
     * 
     * @param Client $client Le client
     * @return string Le nom
     */
    public function getFullName(Client $client): string
    {
        return $client->getNom();
    }

    /**
     * Obtient le nombre de ventes d'un client
     * 
     * @param Client $client Le client
     * @return int Le nombre de ventes
     */
    public function getVentesCount(Client $client): int
    {
        return $client->getVentes()->count();
    }

    /**
     * Vérifie si un client est un client VIP (a un badge)
     * 
     * @param Client $client Le client
     * @return bool True si c'est un client VIP
     */
    public function isVIP(Client $client): bool
    {
        return $this->hasBadge($client);
    }

    /**
     * Valide que le nom ne contient que des caractères valides
     * 
     * @param string $nom Le nom
     * @return bool True si le nom est valide
     */
    public function isValidName(string $nom): bool
    {
        return preg_match("/^[a-zA-ZÀ-ÿ\s\-']+$/", $nom) === 1;
    }

    /**
     * Valide que l'adresse est valide
     * 
     * @param string $adresse L'adresse
     * @return bool True si l'adresse est valide
     */
    public function isValidAddress(string $adresse): bool
    {
        return strlen($adresse) >= 3 && strlen($adresse) <= 150;
    }
}
