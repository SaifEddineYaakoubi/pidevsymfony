<?php

namespace App\Controller\Api;

use App\Service\Api\WeatherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class WeatherController extends AbstractController
{
    #[Route('/api/weather', name: 'api_weather', methods: ['GET'])]
    public function weather(Request $request, WeatherService $weatherService): JsonResponse
    {
        $city = (string) $request->query->get('city', '');

        $lat = $request->query->get('lat');
        $lon = $request->query->get('lon');
        if ($lat !== null && $lon !== null && is_numeric($lat) && is_numeric($lon)) {
            $data = $weatherService->getCurrentWeatherByCoordinates((float) $lat, (float) $lon);
        } else {
            $data = $weatherService->getCurrentWeatherByCity($city);
        }

        return $this->json($data);
    }
}

