<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class FaceRecognitionController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('/api/face/register', name: 'api_face_register', methods: ['POST'])]
    public function registerFace(Request $request, #[CurrentUser] ?Utilisateur $user): JsonResponse
    {
        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $faceDescriptor = $data['descriptor'] ?? null;

        if (!$faceDescriptor) {
            return new JsonResponse(['error' => 'Descripteur facial manquant'], 400);
        }

        // Sauvegarder le descripteur facial (c'est un tableau de 128 nombres)
        $user->setFaceDescriptor(json_encode($faceDescriptor));
        $user->setFaceEnabled(true);

        $this->entityManager->flush();

        return new JsonResponse([
            'success' => true,
            'message' => 'Visage enregistré avec succès !'
        ]);
    }

    #[Route('/api/face/login', name: 'api_face_login', methods: ['POST'])]
    public function loginWithFace(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $faceDescriptor = $data['descriptor'] ?? null;

        if (!$faceDescriptor) {
            return new JsonResponse(['error' => 'Descripteur facial manquant'], 400);
        }

        // Récupérer tous les utilisateurs avec reconnaissance faciale activée
        $users = $this->entityManager->getRepository(Utilisateur::class)
            ->findBy(['faceEnabled' => true]);

        $bestMatch = null;
        $bestDistance = PHP_FLOAT_MAX;
        $threshold = 0.6; // Seuil de similarité (plus bas = plus strict)

        foreach ($users as $user) {
            $storedDescriptor = json_decode($user->getFaceDescriptor(), true);
            
            if (!$storedDescriptor) {
                continue;
            }

            // Calculer la distance euclidienne entre les descripteurs
            $distance = $this->calculateEuclideanDistance($faceDescriptor, $storedDescriptor);

            if ($distance < $bestDistance && $distance < $threshold) {
                $bestDistance = $distance;
                $bestMatch = $user;
            }
        }

        if ($bestMatch) {
            return new JsonResponse([
                'success' => true,
                'userId' => $bestMatch->getIdUser(),
                'email' => $bestMatch->getEmail(),
                'name' => $bestMatch->getPrenom() . ' ' . $bestMatch->getNom(),
                'distance' => $bestDistance
            ]);
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Visage non reconnu'
        ], 404);
    }

    #[Route('/api/face/authenticate', name: 'api_face_authenticate', methods: ['POST'])]
    public function authenticateWithFace(
        Request $request,
        \Symfony\Bundle\SecurityBundle\Security $security
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $userId = $data['userId'] ?? null;

        if (!$userId) {
            return new JsonResponse(['error' => 'ID utilisateur manquant'], 400);
        }

        $user = $this->entityManager->getRepository(Utilisateur::class)->find($userId);

        if (!$user) {
            return new JsonResponse(['error' => 'Utilisateur non trouvé'], 404);
        }

        // Authentifier l'utilisateur
        try {
            $security->login($user, \App\Security\LoginFormAuthenticator::class, 'main');

            // Déterminer la redirection selon le rôle
            $role = strtolower($user->getRole() ?? '');
            
            if ($role === 'admin' || str_starts_with($role, 'role_admin')) {
                $redirectUrl = $this->generateUrl('app_admin_dashboard');
            } elseif ($role === 'responsable_stock' || str_starts_with($role, 'role_stock')) {
                $redirectUrl = $this->generateUrl('app_stock_home');
            } elseif ($role === 'agriculteur' || str_starts_with($role, 'role_agriculteur')) {
                $redirectUrl = $this->generateUrl('app_agriculteur_home');
            } else {
                $redirectUrl = $this->generateUrl('app_home');
            }

            return new JsonResponse([
                'success' => true,
                'redirect' => $redirectUrl,
                'message' => 'Connexion réussie !'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => 'Erreur lors de l\'authentification: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateEuclideanDistance(array $descriptor1, array $descriptor2): float
    {
        if (count($descriptor1) !== count($descriptor2)) {
            return PHP_FLOAT_MAX;
        }

        $sum = 0;
        for ($i = 0; $i < count($descriptor1); $i++) {
            $diff = $descriptor1[$i] - $descriptor2[$i];
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }
}
