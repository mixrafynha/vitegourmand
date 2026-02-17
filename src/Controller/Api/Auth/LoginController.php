<?php

namespace App\Controller\Api\Auth;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class LoginController
{
    #[Route('/api/auth/login', name: 'api_auth_login', methods: ['POST'])]
    public function __invoke(): Response
    {
        // ⚠️ Nunca executado:
        // o firewall json_login intercepta antes
        return new Response('', Response::HTTP_UNAUTHORIZED);
    }
}
