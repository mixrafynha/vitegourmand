<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        // Sua Twig usa "avis", então enviamos algo para não quebrar
        $avis = [
            ['stars' => 5, 'message' => 'Service impeccable, plats délicieux !'],
            ['stars' => 4, 'message' => 'Très bon, rapide et chaleureux.'],
        ];

        return $this->render('home/index.html.twig', [
            'avis' => $avis,
        ]);
    }
}
