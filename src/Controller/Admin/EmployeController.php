<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/employe', name: 'admin_employe_')]
class EmployeController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Lista utilizadores (ajusta se tiveres uma entidade Employe própria)
        $users = $em->getRepository(User::class)->findBy([], ['id' => 'DESC']);

        return $this->render('admin/employe/index.html.twig', [
            'users' => $users,
        ]);
    }
}
