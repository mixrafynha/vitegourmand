<?php

namespace App\Controller\Admin;

use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/commande', name: 'admin_commande_')]
class CommandeController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        $status = $request->query->get('status');

        $qb = $em->getRepository(Commande::class)->createQueryBuilder('c')
            ->leftJoin('c.user', 'u')->addSelect('u')
            ->orderBy('c.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('c.status = :s')->setParameter('s', $status);
        }

        $commandes = $qb->getQuery()->getResult();

        return $this->render('admin/commande/index.html.twig', [
            'commandes' => $commandes,
            'status' => $status,
            'statuses' => [
                Commande::STATUS_PENDING,
                Commande::STATUS_ACCEPTED,
                Commande::STATUS_REFUSED,
                Commande::STATUS_PREPARING,
                Commande::STATUS_READY,
                Commande::STATUS_DELIVERING,
                Commande::STATUS_DELIVERED,
                Commande::STATUS_CANCELLED,
            ],
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Commande $commande): Response
    {
        return $this->render('admin/commande/show.html.twig', [
            'commande' => $commande,
            'statuses' => [
                Commande::STATUS_PENDING,
                Commande::STATUS_ACCEPTED,
                Commande::STATUS_REFUSED,
                Commande::STATUS_PREPARING,
                Commande::STATUS_READY,
                Commande::STATUS_DELIVERING,
                Commande::STATUS_DELIVERED,
                Commande::STATUS_CANCELLED,
            ],
        ]);
    }

    #[Route('/{id}/status', name: 'status', methods: ['POST'])]
    public function updateStatus(Commande $commande, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('commande_status_'.$commande->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF invalido');
        }

        $newStatus = (string) $request->request->get('status');

        $allowed = [
            Commande::STATUS_PENDING,
            Commande::STATUS_ACCEPTED,
            Commande::STATUS_REFUSED,
            Commande::STATUS_PREPARING,
            Commande::STATUS_READY,
            Commande::STATUS_DELIVERING,
            Commande::STATUS_DELIVERED,
            Commande::STATUS_CANCELLED,
        ];

        if (!in_array($newStatus, $allowed, true)) {
            $this->addFlash('error', 'Status inválido.');
            return $this->redirectToRoute('admin_commande_show', ['id' => $commande->getId()]);
        }

        $commande->setStatus($newStatus);
        $em->flush();

        $this->addFlash('success', 'Status atualizado!');
        return $this->redirectToRoute('admin_commande_show', ['id' => $commande->getId()]);
    }
}
