<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

final class JwtCookieSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    public function __construct(private JWTTokenManagerInterface $jwtManager) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): JsonResponse
    {
        $user = $token->getUser();
        if (!is_object($user)) {
            return new JsonResponse(['error' => 'Invalid user'], 500);
        }

        $jwt = $this->jwtManager->create($user);

        $host = $request->getHost();
        $secure = !in_array($host, ['127.0.0.1', 'localhost'], true);

        // ✅ pega roles DIRETO do token (mais confiável)
        $roles = array_map([$this, 'normalizeRole'], $token->getRoleNames());

        $cookie = Cookie::create('BEARER')
            ->withValue($jwt)
            ->withExpires(time() + 900)
            ->withPath('/')
            ->withSecure($secure)
            ->withHttpOnly(true)
            ->withSameSite('Lax');

        $response = new JsonResponse([
            'token' => $jwt,
            'roles' => $roles,
            'redirect' => $this->getRedirectByRole($roles),
        ]);

        $response->headers->setCookie($cookie);

        return $response;
    }

    private function getRedirectByRole(array $roles): string
    {
        if (in_array('ROLE_ADMIN', $roles, true)) return '/admin';
        if (in_array('ROLE_EMPLOYEE', $roles, true)) return '/employee';
        return '/dashboard';
    }

    private function normalizeRole(string $role): string
    {
        $role = strtoupper(trim($role));
        if (!str_starts_with($role, 'ROLE_')) $role = 'ROLE_' . $role;
        if ($role === 'ROLE_EMPLOYE') $role = 'ROLE_EMPLOYEE';
        return $role;
    }
}
