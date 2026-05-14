<?php

namespace App\Service;

use App\Entity\Utilisateur;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Service métier pour la gestion des utilisateurs
 * 
 * Responsabilités:
 * - Valider les règles métier spécifiques aux utilisateurs
 * - Gérer la création et modification d'utilisateurs
 * - Gérer les mots de passe
 * - Gérer les rôles et permissions
 */
class UtilisateurManager
{
    public function __construct(
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    /**
     * Valide un utilisateur selon les règles métier
     * 
     * @param Utilisateur $utilisateur L'utilisateur à valider
     * @return ConstraintViolationListInterface Les violations trouvées
     */
    public function validate(Utilisateur $utilisateur): ConstraintViolationListInterface
    {
        return $this->validator->validate($utilisateur);
    }

    /**
     * Crée un nouvel utilisateur avec validation
     * 
     * @param string $nom Le nom
     * @param string $prenom Le prénom
     * @param string $email L'email
     * @param string $role Le rôle
     * @param string $password Le mot de passe en clair
     * 
     * @return Utilisateur L'utilisateur créé
     * @throws \InvalidArgumentException Si les données sont invalides
     */
    public function createUtilisateur(
        string $nom,
        string $prenom,
        string $email,
        string $role,
        string $password
    ): Utilisateur {
        // Valider que le rôle est valide
        $validRoles = ['admin', 'responsable_stock', 'agriculteur'];
        if (!in_array($role, $validRoles, true)) {
            throw new \InvalidArgumentException(
                sprintf('Le rôle doit être l\'un des suivants: %s', implode(', ', $validRoles))
            );
        }

        $utilisateur = new Utilisateur();
        $utilisateur->setNom($nom);
        $utilisateur->setPrenom($prenom);
        $utilisateur->setEmail($email);
        $utilisateur->setRole($role);
        $utilisateur->setStatut(true);
        $utilisateur->setDateCreation(new \DateTime());

        // Hasher le mot de passe
        $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $password);
        $utilisateur->setMotDePasse($hashedPassword);

        // Valider l'utilisateur créé
        $violations = $this->validate($utilisateur);
        if ($violations->count() > 0) {
            throw new \InvalidArgumentException(
                'L\'utilisateur créé ne respecte pas les contraintes de validation: ' . 
                (string) $violations[0]->getMessage()
            );
        }

        return $utilisateur;
    }

    /**
     * Change le mot de passe d'un utilisateur
     * 
     * @param Utilisateur $utilisateur L'utilisateur
     * @param string $newPassword Le nouveau mot de passe en clair
     * @return Utilisateur L'utilisateur mis à jour
     */
    public function changePassword(Utilisateur $utilisateur, string $newPassword): Utilisateur
    {
        $hashedPassword = $this->passwordHasher->hashPassword($utilisateur, $newPassword);
        $utilisateur->setMotDePasse($hashedPassword);
        return $utilisateur;
    }

    /**
     * Vérifie si un mot de passe est correct
     * 
     * @param Utilisateur $utilisateur L'utilisateur
     * @param string $password Le mot de passe en clair
     * @return bool True si le mot de passe est correct
     */
    public function isPasswordValid(Utilisateur $utilisateur, string $password): bool
    {
        return $this->passwordHasher->isPasswordValid($utilisateur, $password);
    }

    /**
     * Obtient les rôles Symfony complets
     * 
     * @param Utilisateur $utilisateur L'utilisateur
     * @return array Les rôles Symfony
     */
    public function getSymfonyRoles(Utilisateur $utilisateur): array
    {
        return $utilisateur->getRoles();
    }

    /**
     * Vérifie si un utilisateur a un rôle spécifique
     * 
     * @param Utilisateur $utilisateur L'utilisateur
     * @param string $role Le rôle à vérifier
     * @return bool True si l'utilisateur a le rôle
     */
    public function hasRole(Utilisateur $utilisateur, string $role): bool
    {
        return in_array($role, $utilisateur->getRoles(), true);
    }

    /**
     * Calcule l'âge de l'utilisateur
     * 
     * @param Utilisateur $utilisateur L'utilisateur
     * @return int|null L'âge ou null si pas de date de naissance
     */
    public function getAge(Utilisateur $utilisateur): ?int
    {
        return $utilisateur->getAge();
    }

    /**
     * Obtient le nom complet de l'utilisateur
     * 
     * @param Utilisateur $utilisateur L'utilisateur
     * @return string Le nom complet
     */
    public function getFullName(Utilisateur $utilisateur): string
    {
        return trim($utilisateur->getPrenom() . ' ' . $utilisateur->getNom());
    }

    /**
     * Valide que l'email est unique (à utiliser avec une requête en base)
     * 
     * @param string $email L'email à valider
     * @return bool True si l'email est valide
     */
    public function isEmailValid(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valide que le mot de passe est suffisamment fort
     * 
     * @param string $password Le mot de passe
     * @return bool True si le mot de passe est fort
     */
    public function isPasswordStrong(string $password): bool
    {
        // Au minimum 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre
        return strlen($password) >= 8 &&
               preg_match('/[A-Z]/', $password) &&
               preg_match('/[a-z]/', $password) &&
               preg_match('/[0-9]/', $password);
    }

    /**
     * Active un utilisateur
     * 
     * @param Utilisateur $utilisateur L'utilisateur
     * @return Utilisateur L'utilisateur activé
     */
    public function activate(Utilisateur $utilisateur): Utilisateur
    {
        $utilisateur->setStatut(true);
        return $utilisateur;
    }

    /**
     * Désactive un utilisateur
     * 
     * @param Utilisateur $utilisateur L'utilisateur
     * @return Utilisateur L'utilisateur désactivé
     */
    public function deactivate(Utilisateur $utilisateur): Utilisateur
    {
        $utilisateur->setStatut(false);
        return $utilisateur;
    }

    /**
     * Vérifie si un utilisateur est actif
     * 
     * @param Utilisateur $utilisateur L'utilisateur
     * @return bool True si l'utilisateur est actif
     */
    public function isActive(Utilisateur $utilisateur): bool
    {
        return $utilisateur->getStatut();
    }
}
