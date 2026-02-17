<?php

namespace App\Controller\Web;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class OAuthController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectGoogle(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry->getClient('google')->redirect(['email', 'profile']);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(): Response
    {
        // Se chegou aqui, o Authenticator NÃO interceptou.
        // Não dá erro: volta para login com mensagem.
        $this->addFlash('error', 'Google login não foi interceptado pelo Security.');
        return $this->redirectToRoute('app_login');
    }
}
