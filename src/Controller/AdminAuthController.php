<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class AdminAuthController extends AbstractController
{
    #[Route('/admin/login', name: 'app_admin_login')]
    public function login(): Response
    {
        return $this->render('admin/auth/login.html.twig');
    }
}

