<?php

namespace App\Controller\Web;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class GoogleConnectController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start', methods: ['GET'])]
    public function connect(ClientRegistry $clientRegistry): Response
    {
        // scopes: email + perfil (para email, given_name, family_name)
        return $clientRegistry->getClient('google')->redirect(
            ['email', 'profile'],
            []
        );
    }

    // ✅ callback do Google
    // O GoogleAuthenticator vai "pegar" esta rota e autenticar/criar a conta.
    #[Route('/connect/google/check', name: 'connect_google_check', methods: ['GET'])]
    public function check(): Response
    {
        // nunca deve chegar aqui se o firewall/autenticator estiver configurado corretamente
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
