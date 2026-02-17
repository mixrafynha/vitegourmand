<?php

namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

final class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    use TargetPathTrait;

    public function __construct(
        private JWTTokenManagerInterface $jwtManager
    ) {}

    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $user = $token->getUser();
        $roles = $token->getRoleNames();

        // ✅ 1) Decide para onde vai
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $redirectTo = '/admin';
        } elseif (in_array('ROLE_EMPLOYEE', $roles, true)) {
            $redirectTo = '/employee';
        } else {
            // USER normal pode respeitar targetPath
            $redirectTo = '/dashboard';
            if ($request->hasSession()) {
                $targetPath = $this->getTargetPath($request->getSession(), 'main');
                if ($targetPath) {
                    $this->removeTargetPath($request->getSession(), 'main');
                    $redirectTo = $targetPath;
                }
            }
        }

        // ✅ 2) Cria JWT e mete no cookie (para /admin e /dashboard funcionarem no firewall JWT)
        $jwt = $this->jwtManager->create($user);

        $host = $request->getHost();
        $secure = !in_array($host, ['127.0.0.1', 'localhost'], true);

        $cookie = Cookie::create('BEARER')
            ->withValue($jwt)
            ->withExpires(time() + 900) // 15 min
            ->withPath('/')
            ->withSecure($secure)
            ->withHttpOnly(true)
            ->withSameSite('Lax');

        $response = new RedirectResponse($redirectTo);
        $response->headers->setCookie($cookie);

        return $response;
    }
}
