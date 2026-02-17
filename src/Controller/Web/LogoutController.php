<?php
namespace App\Controller\Web;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

final class LogoutController
{
    #[Route('/logout', name: 'app_logout')]
    public function logout(): Response
    {
        $response = new RedirectResponse('/login');
        $response->headers->clearCookie('BEARER', '/', null, true, true, 'Strict');
        return $response;
    }
}
