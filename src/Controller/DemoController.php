<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class DemoController extends AbstractController
{
    #[Route('/admin/demo', name: 'app_admin_demo')]
    public function adminDemo(): Response
    {
        return $this->render('admin/demo.html.twig');
    }

    #[Route('/agriculteur/demo', name: 'app_agriculteur_demo')]
    public function agriculteurDemo(): Response
    {
        return $this->render('front/demo.html.twig');
    }
}

