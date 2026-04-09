<?php
// src/Security/LoginFormAuthenticator.php
namespace App\Security;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    // Use the admin login route name so the authenticator recognizes the login form
    public const LOGIN_ROUTE = 'app_admin_login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private EntityManagerInterface $entityManager
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $email = $request->request->get('email', '');
        $password = $request->request->get('password', '');
        $csrfToken = $request->request->get('_csrf_token');

        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);

        // Check if user exists and is active
        $user = $this->entityManager->getRepository(Utilisateur::class)->findOneBy(['email' => $email]);

        if (!$user) {
            throw new CustomUserMessageAuthenticationException('Email non trouvé.');
        }

        if (!$user->getStatut()) {
            throw new CustomUserMessageAuthenticationException('Votre compte est désactivé. Veuillez contacter l\'administrateur.');
        }

        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Get the user
        $user = $token->getUser();

        // Prefer using the user's raw role field if present (entity provides getRole())
        $roleField = null;
        if (is_object($user) && method_exists($user, 'getRole')) {
            $roleField = $user->getRole();
        }

        // Map roleField if available
        if ($roleField) {
            $rf = strtolower($roleField);
            if ($rf === 'admin' || str_starts_with($rf, 'role_admin')) {
                $target = 'app_admin_dashboard';
            } elseif ($rf === 'responsable_stock' || str_starts_with($rf, 'role_stock')) {
                $target = 'app_stock_home';
            } elseif ($rf === 'agriculteur' || str_starts_with($rf, 'role_agriculteur')) {
                $target = 'app_agriculteur_home';
            } else {
                $target = 'app_home';
            }

            // If there is a saved target path (user was trying to access a protected URL), respect it
            $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
            if ($targetPath) {
                return new RedirectResponse($targetPath);
            }

            return new RedirectResponse($this->urlGenerator->generate($target));
        }

        // Fallback: use computed roles on the user object
        $roles = [];
        if (is_object($user) && method_exists($user, 'getRoles')) {
            $roles = $user->getRoles();
        }

        if (in_array('ROLE_ADMIN', $roles)) {
            $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
            if ($targetPath) {
                return new RedirectResponse($targetPath);
            }
            return new RedirectResponse($this->urlGenerator->generate('app_admin_dashboard'));
        } elseif (in_array('ROLE_STOCK', $roles)) {
            return new RedirectResponse($this->urlGenerator->generate('app_stock_home'));
        } elseif (in_array('ROLE_AGRICULTEUR', $roles)) {
            return new RedirectResponse($this->urlGenerator->generate('app_agriculteur_home'));
        }

        // Default fallback -- send the user to app_home which itself will redirect safely
        return new RedirectResponse($this->urlGenerator->generate('app_home'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}

