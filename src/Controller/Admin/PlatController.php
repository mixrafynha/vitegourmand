<?php

namespace App\Controller\Admin;

use App\Entity\Plat;
use App\Form\PlatType;
use App\Repository\PlatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/plat')]
final class PlatController extends AbstractController
{
    #[Route('/', name: 'admin_plat_index', methods: ['GET'])]
    public function index(PlatRepository $repo): Response
    {
        return $this->render('admin/plat/index.html.twig', [
            'plats' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_plat_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $plat = new Plat();
        $form = $this->createForm(PlatType::class, $plat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($plat);
            $em->flush();
            return $this->redirectToRoute('admin_plat_index');
        }

        return $this->render('admin/plat/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_plat_edit', methods: ['GET', 'POST'])]
    public function edit(Plat $plat, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PlatType::class, $plat);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('admin_plat_index');
        }

        return $this->render('admin/plat/edit.html.twig', [
            'form' => $form->createView(),
            'plat' => $plat,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_plat_delete', methods: ['POST'])]
    public function delete(Plat $plat, EntityManagerInterface $em): Response
    {
        $em->remove($plat);
        $em->flush();
        return $this->redirectToRoute('admin_plat_index');
    }
}
