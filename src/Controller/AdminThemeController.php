<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/theme', name: 'app_admin_theme_')]
final class AdminThemeController extends AbstractController
{
    #[Route('/{page}', name: 'page', requirements: ['page' => '.+'])]
    public function page(string $page): Response
    {
        // Allow only safe characters; the {page} may contain subpaths like "tables/simple".
        // We map it to our extracted partials naming: slashes are replaced by "__".
        $safe = preg_replace('/[^a-zA-Z0-9_\/-]/', '', $page) ?? '';
        $safe = trim($safe, '/');

        if ($safe === '') {
            $safe = 'widgets';
        }

        $partial = 'admin/adminlte_pages/' . str_replace('/', '__', $safe) . '.html.twig';

        return $this->render('admin/pages/theme_page.html.twig', [
            'adminlte_partial' => $partial,
            'page' => $safe,
        ]);
    }
}

