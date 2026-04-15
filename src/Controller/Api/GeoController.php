<?php

namespace App\Controller\Api;

use App\Service\Api\GeoLocationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class GeoController extends AbstractController
{
    #[Route('/api/geocode', name: 'api_geocode', methods: ['GET'])]
    public function geocode(Request $request, GeoLocationService $geoLocationService): JsonResponse
    {
        $q = (string) $request->query->get('q', '');
        $data = $geoLocationService->geocode($q);

        return $this->json($data);
    }
}

