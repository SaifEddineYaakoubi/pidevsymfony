<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class StockController extends AbstractController
{
    #[Route('/stock', name: 'app_stock_home')]
    public function home(): Response
    {
        return $this->render('stock/pages/home.html.twig');
    }

    #[Route('/stock/about', name: 'app_stock_about')]
    public function about(): Response
    {
        return $this->render('stock/pages/about.html.twig');
    }

    #[Route('/stock/services', name: 'app_stock_services')]
    public function services(): Response
    {
        return $this->render('stock/pages/services.html.twig');
    }

    #[Route('/stock/testimonials', name: 'app_stock_testimonials')]
    public function testimonials(): Response
    {
        return $this->render('stock/pages/testimonials.html.twig');
    }

    #[Route('/stock/blog', name: 'app_stock_blog')]
    public function blog(): Response
    {
        return $this->render('stock/pages/blog.html.twig');
    }

    #[Route('/stock/blog/{slug}', name: 'app_stock_blog_details')]
    public function blogDetails(string $slug): Response
    {
        return $this->render('stock/pages/blog_details.html.twig', [
            'slug' => $slug,
        ]);
    }

    #[Route('/stock/contact', name: 'app_stock_contact')]
    public function contact(): Response
    {
        return $this->render('stock/pages/contact.html.twig');
    }
}

