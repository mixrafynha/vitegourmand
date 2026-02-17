<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class PagesController extends AbstractController
{
    // ✅ NOVO: rota certa do site
    #[Route('/menus', name: 'app_menu', methods: ['GET'])]
    public function menu(): Response
    {
        // ✅ aqui renderiza o Twig da tua página de menus
        // ajusta o caminho se o teu template tiver outro nome
        return $this->render('menu/index.html.twig');
    }

    // ✅ manter compatibilidade: /menu -> /menus
    #[Route('/menu', name: 'app_menu_legacy', methods: ['GET'])]
    public function menuLegacy(): Response
    {
        return $this->redirectToRoute('app_menu');
    }

    #[Route('/contact', name: 'app_contact', methods: ['GET'])]
    public function contact(): Response
    {
        return $this->render('contact/index.html.twig');
        // se ainda não tens twig, podes deixar placeholder:
        // return new Response('Página Contact (placeholder)');
    }

    #[Route('/mentions-legales', name: 'app_mentions', methods: ['GET'])]
    public function mentions(): Response
    {
        return $this->render('legal/mentions.html.twig');
        // ou placeholder
    }

    #[Route('/conditions-generales', name: 'app_conditions', methods: ['GET'])]
    public function conditions(): Response
    {
        return $this->render('legal/conditions.html.twig');
        // ou placeholder
    }

    #[Route('/cookies', name: 'app_cookies', methods: ['GET'])]
    public function cookies(): Response
    {
        return $this->render('legal/cookies.html.twig');
        // ou placeholder
    }
}
