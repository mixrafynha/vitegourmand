<?php

namespace App\Controller\Api;

use App\Entity\Restaurant;
use App\Repository\RestaurantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[Route('/api/restaurants')]
final class RestaurantController extends AbstractController
{
    #[Route('', name: 'restaurants_list', methods: ['GET'])]
    public function list(RestaurantRepository $repo): JsonResponse
    {
        $items = $repo->findBy([], ['id' => 'DESC']);

        return $this->json(array_map(fn (Restaurant $r) => [
            'id' => $r->getId(),
            'name' => $r->getName(),
            'address' => $r->getAddress(),
            'createdAt' => $r->getCreatedAt()?->format(DATE_ATOM),
        ], $items));
    }

    #[Route('/{id}', name: 'restaurants_show', methods: ['GET'])]
    public function show(Restaurant $restaurant): JsonResponse
    {
        return $this->json([
            'id' => $restaurant->getId(),
            'name' => $restaurant->getName(),
            'address' => $restaurant->getAddress(),
            'createdAt' => $restaurant->getCreatedAt()?->format(DATE_ATOM),
        ]);
    }

    // 🔒 protegido (token obrigatório pelo access_control ^/api)
    #[Route('', name: 'restaurants_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        if (!is_array($data)) {
            return $this->json(['error' => 'INVALID_JSON'], 400);
        }

        $name = trim((string)($data['name'] ?? ''));
        $address = trim((string)($data['address'] ?? ''));

        if ($name === '') {
            return $this->json(['error' => 'NAME_REQUIRED'], 400);
        }

        $restaurant = new Restaurant();
        $restaurant->setName($name);
        $restaurant->setAddress($address !== '' ? $address : null);

        $em->persist($restaurant);
        $em->flush();

        return $this->json([
            'id' => $restaurant->getId(),
            'name' => $restaurant->getName(),
            'address' => $restaurant->getAddress(),
            'createdAt' => $restaurant->getCreatedAt()?->format(DATE_ATOM),
        ], 201);
    }

    #[Route('/{id}', name: 'restaurants_update', methods: ['PUT'])]
    public function update(Restaurant $restaurant, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent() ?: '{}', true);
        if (!is_array($data)) {
            return $this->json(['error' => 'INVALID_JSON'], 400);
        }

        if (array_key_exists('name', $data)) {
            $name = trim((string)$data['name']);
            if ($name === '') {
                return $this->json(['error' => 'NAME_REQUIRED'], 400);
            }
            $restaurant->setName($name);
        }

        if (array_key_exists('address', $data)) {
            $address = trim((string)$data['address']);
            $restaurant->setAddress($address !== '' ? $address : null);
        }

        $em->flush();

        return $this->json([
            'id' => $restaurant->getId(),
            'name' => $restaurant->getName(),
            'address' => $restaurant->getAddress(),
            'createdAt' => $restaurant->getCreatedAt()?->format(DATE_ATOM),
        ]);
    }

    #[Route('/{id}', name: 'restaurants_delete', methods: ['DELETE'])]
    public function delete(Restaurant $restaurant, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($restaurant);
        $em->flush();

        return $this->json(null, 204);
    }
}
