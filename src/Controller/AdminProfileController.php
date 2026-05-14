<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/profile')]
class AdminProfileController extends AbstractController
{
    #[Route('/update', name: 'app_admin_profile_update', methods: ['POST'])]
    public function update(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        /** @var Utilisateur $user */
        $user = $this->getUser();
        
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Utilisateur non connecté'], 403);
        }

        $nom = (string) $request->request->get('nom', '');
        $prenom = (string) $request->request->get('prenom', '');
        $email = (string) $request->request->get('email', '');
        $currentPassword = (string) $request->request->get('current_password', '');
        $newPassword = $request->request->get('new_password');
        $profilePictureData = $request->request->get('profile_picture_data');

        // Validation basique
        if (!$nom || !$prenom || !$email || !$currentPassword) {
            return new JsonResponse(['success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires'], 400);
        }

        // Vérifier le mot de passe actuel
        if (!$passwordHasher->isPasswordValid($user, $currentPassword)) {
            return new JsonResponse(['success' => false, 'message' => 'Le mot de passe actuel est incorrect'], 400);
        }

        // Mise à jour des informations de base
        $user->setNom($nom);
        $user->setPrenom($prenom);
        $user->setEmail($email);

        // Mise à jour du mot de passe si fourni
        if ($newPassword) {
            $newPasswordStr = (string) $newPassword;
            if (strlen($newPasswordStr) < 6) {
                return new JsonResponse(['success' => false, 'message' => 'Le nouveau mot de passe doit faire au moins 6 caractères'], 400);
            }
            $hashedPassword = $passwordHasher->hashPassword($user, $newPasswordStr);
            $user->setMotDePasse($hashedPassword);
        }

        // Gestion de l'upload de la photo de profil
        if ($profilePictureData) {
            try {
                $filename = $this->saveProfilePicture($user, (string) $profilePictureData);
                $user->setProfilePicture($filename);
            } catch (\Exception $e) {
                return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'upload de l\'image: ' . $e->getMessage()], 400);
            }
        }

        $entityManager->flush();

        return new JsonResponse([
            'success' => true, 
            'message' => 'Profil mis à jour avec succès',
            'user' => [
                'nom' => $user->getNom(),
                'prenom' => $user->getPrenom(),
                'email' => $user->getEmail(),
                'profilePicture' => $user->getProfilePicture()
            ]
        ]);
    }

    private function saveProfilePicture(Utilisateur $user, string $base64Image): string
    {
        // Décoder l'image base64
        $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $base64Image);
        $imageData = str_replace(' ', '+', (string) $imageData);
        $imageData = base64_decode($imageData);

        if ($imageData === false) {
            throw new \Exception('Image invalide');
        }

        // Créer le dossier si nécessaire
        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/profiles/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Supprimer l'ancienne photo si elle existe
        if ($user->getProfilePicture()) {
            $oldFile = $uploadDir . $user->getProfilePicture();
            if (file_exists($oldFile)) {
                unlink($oldFile);
            }
        }

        // Générer un nom de fichier unique
        $filename = 'profile_' . $user->getIdUser() . '_' . time() . '.jpg';
        $filepath = $uploadDir . $filename;

        // Sauvegarder l'image
        if (file_put_contents($filepath, $imageData) === false) {
            throw new \Exception('Impossible de sauvegarder l\'image');
        }

        return $filename;
    }
}
