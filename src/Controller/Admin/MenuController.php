<?php

namespace App\Controller\Admin;

use App\Entity\Menu;
use App\Form\MenuType;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Filesystem\Filesystem;

#[Route('/admin/menu')]
class MenuController extends AbstractController
{
    #[Route('/', name: 'admin_menu_index', methods: ['GET'])]
    public function index(MenuRepository $repo): Response
    {
        return $this->render('admin/menu/index.html.twig', [
            'menus' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_menu_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $menu = new Menu();
        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile|null $file */
            $file = $form->get('imageFile')->getData();

            if ($file instanceof UploadedFile) {

                $filename = uniqid('menu_', true) . '.' . ($file->guessExtension() ?: 'jpg');

                $file->move(
                    $this->getParameter('uploads_dir'),
                    $filename
                );

                // 🔥 guarda na coluna image_url
                $menu->setImageUrl('/uploads/' . $filename);
            }

            $em->persist($menu);
            $em->flush();

            $this->addFlash('success', 'Menu criado com sucesso.');

            return $this->redirectToRoute('admin_menu_index');
        }

        return $this->render('admin/menu/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_menu_edit', methods: ['GET', 'POST'])]
    public function edit(Menu $menu, Request $request, EntityManagerInterface $em): Response
    {
        $oldImageUrl = $menu->getImageUrl();

        $form = $this->createForm(MenuType::class, $menu);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile|null $file */
            $file = $form->get('imageFile')->getData();

            if ($file instanceof UploadedFile) {

                $filename = uniqid('menu_', true) . '.' . ($file->guessExtension() ?: 'jpg');

                $file->move(
                    $this->getParameter('uploads_dir'),
                    $filename
                );

                $menu->setImageUrl('/uploads/' . $filename);

                // apagar imagem antiga
                if ($oldImageUrl && str_starts_with($oldImageUrl, '/uploads/')) {

                    $oldPath = $this->getParameter('kernel.project_dir') . '/public' . $oldImageUrl;

                    $fs = new Filesystem();
                    if ($fs->exists($oldPath)) {
                        $fs->remove($oldPath);
                    }
                }
            }

            $em->flush();

            $this->addFlash('success', 'Menu atualizado.');

            return $this->redirectToRoute('admin_menu_index');
        }

        return $this->render('admin/menu/edit.html.twig', [
            'form' => $form->createView(),
            'menu' => $menu,
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_menu_delete', methods: ['POST'])]
    public function delete(Menu $menu, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_menu_' . $menu->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('admin_menu_index');
        }

        $imageUrl = $menu->getImageUrl();

        if ($imageUrl && str_starts_with($imageUrl, '/uploads/')) {

            $path = $this->getParameter('kernel.project_dir') . '/public' . $imageUrl;

            $fs = new Filesystem();
            if ($fs->exists($path)) {
                $fs->remove($path);
            }
        }

        $em->remove($menu);
        $em->flush();

        $this->addFlash('success', 'Menu supprimé.');

        return $this->redirectToRoute('admin_menu_index');
    }
}
