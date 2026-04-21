<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class ResetPasswordController extends AbstractController
{
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Affiche le formulaire pour demander un reset de mot de passe
     */
    #[Route('/reset-password', name: 'app_forgot_password_request')]
    public function request(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();

            $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy([
                'email' => $email,
            ]);

            // Dans ResetPasswordController.php, méthode request()
            if ($user) {
                try {
                    $resetToken = $this->resetPasswordHelper->generateResetToken($user);

                    $emailMessage = (new TemplatedEmail())
                        ->from(new Address('noreply@smartfarm.com', 'SmartFarm'))
                        ->to($user->getEmail())
                        ->subject('Réinitialisation de votre mot de passe SmartFarm')
                        ->htmlTemplate('reset_password/email.html.twig')
                        ->context([
                            'resetToken' => $resetToken,
                        ]);

                    $mailer->send($emailMessage);

                    // Ajoute un flash message pour confirmer
                    $this->addFlash('success', 'Un email a été envoyé à ' . $user->getEmail());

                } catch (\Exception $e) {
                    $this->addFlash('error', 'Erreur lors de l\'envoi de l\'email: ' . get_class($e) . ' - ' . $e->getMessage());
                }
            } else {
                $this->addFlash('error', "Aucun compte n'a été trouvé avec l'email " . $email);
                return $this->redirectToRoute('app_forgot_password_request');
            }

            // Redirection vers la page de login
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form,
        ]);
    }

    /**
     * Page de confirmation après la demande
     */
    #[Route('/reset-password/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        return $this->render('reset_password/check_email.html.twig');
    }

    /**
     * Réinitialisation du mot de passe
     */
    #[Route('/reset-password/reset/{token}', name: 'app_reset_password')]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, string $token = null): Response
    {
        if (!$token) {
            throw $this->createNotFoundException('Token manquant.');
        }

        try {
            /** @var Utilisateur $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('error', 'Le lien de réinitialisation est invalide ou a expiré. Veuillez refaire une demande.');
            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Changer le mot de passe
            $plainPassword = $form->get('plainPassword')->getData();
            $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setMotDePasse($hashedPassword); // Utilise setMotDePasse() ou setPassword()
            $this->entityManager->flush();

            // Supprimer la demande de reset
            $this->resetPasswordHelper->removeResetRequest($token);

            $this->addFlash('success', 'Votre mot de passe a été réinitialisé avec succès ! Veuillez vous connecter.');

            // Redirection vers la page de login
            return $this->redirectToRoute('app_login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }
}