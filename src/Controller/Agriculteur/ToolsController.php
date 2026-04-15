<?php

namespace App\Controller\Agriculteur;

use App\Service\Api\GeoLocationService;
use App\Service\Api\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ToolsController extends AbstractController
{
    #[Route('/agriculteur/tools/meteo', name: 'agri_tools_meteo', methods: ['GET'])]
    public function meteo(Request $request, WeatherService $weatherService): Response
    {
        $city = (string) $request->query->get('city', 'Tunis');
        $weather = $weatherService->getCurrentWeatherByCity($city);

        return $this->render('agriculteur/tools/meteo.html.twig', [
            'city' => $city,
            'weather' => $weather,
        ]);
    }

    #[Route('/agriculteur/tools/carte', name: 'agri_tools_carte', methods: ['GET'])]
    public function carte(Request $request, GeoLocationService $geoLocationService): Response
    {
        $q = (string) $request->query->get('q', 'Tunis');
        $geo = $geoLocationService->geocode($q);

        return $this->render('agriculteur/tools/carte.html.twig', [
            'q' => $q,
            'geo' => $geo,
        ]);
    }
}

