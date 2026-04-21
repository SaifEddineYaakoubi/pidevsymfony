<?php
// src/Controller/AuthController.php
namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\UtilisateurType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    #[Route('/admin/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // Votre code de login existant
        if ($this->getUser()) {
            $user = $this->getUser();
            $roles = $user->getRoles();

            if (in_array('ROLE_ADMIN', $roles)) {
                return $this->redirectToRoute('app_admin_utilisateur_index');
            } elseif (in_array('ROLE_STOCK', $roles)) {
                return $this->redirectToRoute('app_stock_dashboard');
            } elseif (in_array('ROLE_AGRICULTEUR', $roles)) {
                return $this->redirectToRoute('app_agriculteur_dashboard');
            }
        }

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        // ✅ Correction : S'assurer que $lastUsername n'est pas NULL
        if ($lastUsername === null) {
            $lastUsername = '';
        }

        return $this->render('admin/auth/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/admin/logout', name: 'app_logout')]
    public function logout(): void
    {
        // Cette méthode peut être vide, elle sera interceptée par le firewall
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        // Votre code d'inscription
        if ($this->getUser()) {
            return $this->redirectToRoute('app_admin_utilisateur_index');
        }

        $user = new Utilisateur();
        $form = $this->createForm(UtilisateurType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('mot_de_passe')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setMotDePasse($hashedPassword);
            }

            $user->setDateCreation(new \DateTime());
            $user->setStatut(true);

            if (!$user->getRole()) {
                $user->setRole('agriculteur');
            }

            $entityManager->persist($user);
            $entityManager->flush();

            // Sauvegarder le descripteur facial si fourni
            $faceDescriptor = $request->request->get('face_descriptor');
            if ($faceDescriptor) {
                $user->setFaceDescriptor($faceDescriptor);
                $user->setFaceEnabled(true);
                $entityManager->flush();
            }

            $this->addFlash('success', 'Inscription réussie ! Vous pouvez maintenant vous connecter.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('admin/auth/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/setup-faceid', name: 'app_setup_faceid')]
    public function setupFaceId(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        return $this->render('admin/profile/setup_faceid.html.twig');
    }
}