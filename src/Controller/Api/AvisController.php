<?php

namespace App\Controller\Api;

use App\Repository\AvisRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

final class AvisController extends AbstractController
{
    #[Route('/api/avis', name: 'api_avis_list', methods: ['GET'])]
    public function list(Request $request, AvisRepository $avisRepo): JsonResponse
    {
        $limit = (int) $request->query->get('limit', 9);
        $limit = max(1, min($limit, 50));

        $items = $avisRepo->findLatestVerified($limit);

        $payload = array_map(static function($a) {
            return [
                'id' => $a->getId(),
                'stars' => (int) $a->getStars(),
                'message' => (string) $a->getMessage(),
                'createdAt' => $a->getCreatedAt()?->format(DATE_ATOM),
                // opcional (se quiser mostrar nome):
                // 'author' => $a->getUser()?->getFirstName() ?? 'Client',
            ];
        }, $items);

        return $this->json([
            'success' => true,
            'items' => $payload
        ]);
    }
}
