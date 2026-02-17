<?php

namespace App\Controller\Api;

use App\Entity\Menu;
use App\Repository\MenuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/menus')]
final class MenuApiController extends AbstractController
{
    #[Route('', name: 'api_menus_list', methods: ['GET'])]
    public function list(Request $request, MenuRepository $repo): JsonResponse
    {
        $page  = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, max(1, (int) $request->query->get('limit', 12)));

        $filters = [
            'q'        => $request->query->get('q', ''),
            'minPrice' => $request->query->get('minPrice', ''),
            'maxPrice' => $request->query->get('maxPrice', ''),
            'sort'     => $request->query->get('sort', 'createdAt'),
            'order'    => $request->query->get('order', 'DESC'),
        ];

        [$items, $total] = $repo->search($filters, $page, $limit, true);

        $data = array_map(function (Menu $m) {
            $payload = [
                'id' => $m->getId(),
                'name' => $m->getName(),
                'description' => $m->getDescription(),
                'price' => (float) $m->getPrice(),

                // stock real + reservado + disponível
                'stock' => $m->getStock(),
                'reserved' => method_exists($m, 'getReserved') ? $m->getReserved() : 0,
                'availableStock' => method_exists($m, 'getAvailableStock') ? $m->getAvailableStock() : $m->getStock(),
                'isSoldOut' => method_exists($m, 'isSoldOut') ? $m->isSoldOut() : ($m->getStock() <= 0),

                // imagem principal (cards)
                'imageUrl' => $m->getImageUrl(),

                // infos extra (se existirem na entity)
                'ingredients' => method_exists($m, 'getIngredients') ? $m->getIngredients() : null,
                'allergens' => method_exists($m, 'getAllergens') ? $m->getAllergens() : null,

                // 3 imagens para o modal (main + extras)
                'images' => method_exists($m, 'getImages') ? $m->getImages() : array_values(array_filter([$m->getImageUrl()])),
                'isActive' => $m->isActive(),
            ];

            return $payload;
        }, $items);

        return new JsonResponse([
            'items' => $data,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
            ],
        ]);
    }

    // ✅ rota só aceita números (evita /api/menus/{id} dar 500)
    #[Route('/{id<\d+>}', name: 'api_menus_detail', methods: ['GET'])]
    public function detail(int $id, MenuRepository $repo): JsonResponse
    {
        $menu = $repo->find($id);
        if (!$menu || !$menu->isActive()) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse([
            'id' => $menu->getId(),
            'name' => $menu->getName(),
            'description' => $menu->getDescription(),
            'price' => (float) $menu->getPrice(),

            'stock' => $menu->getStock(),
            'reserved' => method_exists($menu, 'getReserved') ? $menu->getReserved() : 0,
            'availableStock' => method_exists($menu, 'getAvailableStock') ? $menu->getAvailableStock() : $menu->getStock(),
            'isSoldOut' => method_exists($menu, 'isSoldOut') ? $menu->isSoldOut() : ($menu->getStock() <= 0),

            'imageUrl' => $menu->getImageUrl(),
            'images' => method_exists($menu, 'getImages') ? $menu->getImages() : array_values(array_filter([$menu->getImageUrl()])),

            'ingredients' => method_exists($menu, 'getIngredients') ? $menu->getIngredients() : null,
            'allergens' => method_exists($menu, 'getAllergens') ? $menu->getAllergens() : null,

            'isActive' => $menu->isActive(),
        ]);
    }

    #[IsGranted('ROLE_EMPLOYEE')]
    #[Route('', name: 'api_menus_create', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        $payload = json_decode($request->getContent() ?: '{}', true) ?: [];

        $menu = (new Menu())
            ->setName((string)($payload['name'] ?? ''))
            ->setDescription($payload['description'] ?? null)
            ->setPrice((string)($payload['price'] ?? '0.00'))
            ->setStock((int)($payload['stock'] ?? 0))
            ->setImageUrl($payload['imageUrl'] ?? null)
            ->setIsActive((bool)($payload['isActive'] ?? true));

        // extras (não quebra se entity ainda não tem)
        if (method_exists($menu, 'setIngredients') && array_key_exists('ingredients', $payload)) {
            $menu->setIngredients($payload['ingredients']);
        }
        if (method_exists($menu, 'setAllergens') && array_key_exists('allergens', $payload)) {
            $menu->setAllergens($payload['allergens']);
        }
        if (method_exists($menu, 'setExtraImages') && array_key_exists('extraImages', $payload)) {
            $menu->setExtraImages(is_array($payload['extraImages']) ? $payload['extraImages'] : null);
        }

        $errors = $validator->validate($menu);
        if (count($errors) > 0) {
            return new JsonResponse(['message' => (string) $errors], 422);
        }

        $em->persist($menu);
        $em->flush();

        return new JsonResponse(['id' => $menu->getId()], 201);
    }

    #[IsGranted('ROLE_EMPLOYEE')]
    #[Route('/{id<\d+>}', name: 'api_menus_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        MenuRepository $repo,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $menu = $repo->find($id);
        if (!$menu) {
            throw new NotFoundHttpException();
        }

        $payload = json_decode($request->getContent() ?: '{}', true) ?: [];

        if (array_key_exists('name', $payload)) $menu->setName((string)$payload['name']);
        if (array_key_exists('description', $payload)) $menu->setDescription($payload['description']);
        if (array_key_exists('price', $payload)) $menu->setPrice((string)$payload['price']);
        if (array_key_exists('stock', $payload)) $menu->setStock((int)$payload['stock']);
        if (array_key_exists('imageUrl', $payload)) $menu->setImageUrl($payload['imageUrl']);
        if (array_key_exists('isActive', $payload)) $menu->setIsActive((bool)$payload['isActive']);

        // extras
        if (method_exists($menu, 'setIngredients') && array_key_exists('ingredients', $payload)) {
            $menu->setIngredients($payload['ingredients']);
        }
        if (method_exists($menu, 'setAllergens') && array_key_exists('allergens', $payload)) {
            $menu->setAllergens($payload['allergens']);
        }
        if (method_exists($menu, 'setExtraImages') && array_key_exists('extraImages', $payload)) {
            $menu->setExtraImages(is_array($payload['extraImages']) ? $payload['extraImages'] : null);
        }

        $errors = $validator->validate($menu);
        if (count($errors) > 0) {
            return new JsonResponse(['message' => (string) $errors], 422);
        }

        $em->flush();
        return new JsonResponse(['ok' => true]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/{id<\d+>}', name: 'api_menus_delete', methods: ['DELETE'])]
    public function delete(int $id, MenuRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $menu = $repo->find($id);
        if (!$menu) {
            throw new NotFoundHttpException();
        }

        $em->remove($menu);
        $em->flush();

        return new JsonResponse(['ok' => true]);
    }
}
