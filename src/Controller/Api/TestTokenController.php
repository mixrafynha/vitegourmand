<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api')]
final class TestTokenController extends AbstractController
{
    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['error' => 'TOKEN_INVALID_OR_MISSING'], 401);
        }

        return $this->json([
            'id' => method_exists($user, 'getId') ? $user->getId() : null,
            'email' => $user->getUserIdentifier(),
        ]);
    }
}
