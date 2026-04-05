<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'app_admin_pages_')]
final class AdminPagesController extends AbstractController
{
    #[Route('/starter', name: 'starter')]
    public function starter(): Response
    {
        return $this->render('admin/pages/starter.html.twig');
    }

    #[Route('/widgets', name: 'widgets')]
    public function widgets(): Response
    {
        return $this->render('admin/pages/widgets.html.twig');
    }

    #[Route('/tables/simple', name: 'tables_simple')]
    public function tablesSimple(): Response
    {
        return $this->render('admin/pages/tables_simple.html.twig');
    }

    #[Route('/tables/data', name: 'tables_data')]
    public function tablesData(): Response
    {
        return $this->render('admin/pages/tables_data.html.twig');
    }

    #[Route('/calendar', name: 'calendar')]
    public function calendar(): Response
    {
        return $this->render('admin/pages/calendar.html.twig');
    }

    #[Route('/kanban', name: 'kanban')]
    public function kanban(): Response
    {
        return $this->render('admin/pages/kanban.html.twig');
    }
}

