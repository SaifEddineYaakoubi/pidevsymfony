<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class AuthController extends AbstractController
{
    #[Route('/', name: 'app_root')]
    public function root(): Response
    {
        return $this->redirectToRoute('app_login');
    }

    #[Route('/login', name: 'app_login')]
    public function login(Request $request): Response
    {
        // Demo-only: allow quick role switching without implementing Security yet.
        // Example: /login?as=admin|agriculteur|stock
        $as = strtolower((string) $request->query->get('as', ''));

        if ($as !== '') {
            return $this->redirectAfterRole($as);
        }

        return $this->render('security/login.html.twig');
    }

    #[Route('/after-login', name: 'app_after_login')]
    public function afterLogin(): Response
    {
        // When Symfony Security is enabled, we'll use the authenticated user roles here.
        return $this->redirectToRoute('app_agriculteur_home');
    }

    private function redirectAfterRole(string $role): RedirectResponse
    {
        return match ($role) {
            'admin' => $this->redirectToRoute('app_admin_dashboard'),
            'agriculteur' => $this->redirectToRoute('app_agriculteur_home'),
            'stock', 'responsable', 'responsable_stock' => $this->redirectToRoute('app_stock_home'),
            default => $this->redirectToRoute('app_agriculteur_home'),
        };
    }
}

