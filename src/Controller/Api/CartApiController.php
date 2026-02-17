<?php

namespace App\Controller\Api;

use App\Entity\Menu;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/cart')]
final class CartApiController extends AbstractController
{
    #[Route('', methods: ['GET'])]
    public function get(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$request->hasSession()) {
            return new JsonResponse(['items' => []]);
        }

        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $items = $session->get('cart_items', []);
        if (!is_array($items)) $items = [];

        // enriquecer com info atual (stock, etc.)
        $out = [];
        foreach ($items as $it) {
            $menuId = (int)($it['id'] ?? 0);
            $qty = max(1, (int)($it['qty'] ?? 1));
            if ($menuId <= 0) continue;

            /** @var Menu|null $m */
            $m = $em->find(Menu::class, $menuId);
            if (!$m || !$m->isActive()) continue;

            $out[] = $this->formatCartItem($m, $qty);
        }

        // opcional: guardar já normalizado
        $session->set('cart_items', array_map(fn($x) => ['id' => $x['id'], 'qty' => $x['qty']], $out));

        return new JsonResponse(['items' => $out]);
    }

    #[Route('/sync', methods: ['POST'])]
    public function sync(Request $request, EntityManagerInterface $em): JsonResponse
    {
        if (!$request->hasSession()) {
            return new JsonResponse(['message' => 'Sessão indisponível'], 500);
        }

        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }

        $payload = json_decode($request->getContent() ?: '{}', true) ?: [];
        $incoming = $payload['items'] ?? [];

        // carrinho atual na sessão (apenas id/qty)
        $current = $session->get('cart_items', []);
        if (!is_array($current)) $current = [];

        $oldMap = [];
        foreach ($current as $it) {
            $oldMap[(int)($it['id'] ?? 0)] = (int)($it['qty'] ?? 0);
        }

        // novo map normalizado
        $newMap = [];
        foreach ($incoming as $it) {
            $id  = (int)($it['id'] ?? 0);
            $qty = (int)($it['qty'] ?? 0);
            if ($id > 0 && $qty > 0) {
                $newMap[$id] = max(1, $qty);
            }
        }

        try {
            return $em->wrapInTransaction(function () use ($em, $session, $oldMap, $newMap) {

                // 1) aplicar deltas com lock
                foreach ($newMap as $menuId => $qty) {
                    /** @var Menu|null $menu */
                    $menu = $em->find(Menu::class, $menuId, LockMode::PESSIMISTIC_WRITE);
                    if (!$menu || !$menu->isActive()) {
                        unset($newMap[$menuId]);
                        continue;
                    }

                    $prev = $oldMap[$menuId] ?? 0;
                    $delta = $qty - $prev;

                    if ($delta > 0 && $menu->getAvailableStock() < $delta) {
                        return new JsonResponse([
                            'message' => 'Stock insuffisant: ' . $menu->getName(),
                            'menuId' => $menu->getId(),
                            'availableStock' => $menu->getAvailableStock(),
                        ], 409);
                    }

                    $menu->setReserved($menu->getReserved() + $delta);
                }

                // 2) removidos -> libertar reservas antigas
                foreach ($oldMap as $menuId => $prevQty) {
                    if (!isset($newMap[$menuId])) {
                        /** @var Menu|null $menu */
                        $menu = $em->find(Menu::class, $menuId, LockMode::PESSIMISTIC_WRITE);
                        if ($menu) {
                            $menu->setReserved($menu->getReserved() - $prevQty);
                        }
                    }
                }

                // 3) salvar sessão com id/qty
                $session->set('cart_items', array_map(
                    fn($id) => ['id' => $id, 'qty' => $newMap[$id]],
                    array_keys($newMap)
                ));

                $em->flush();

                // 4) devolver carrinho completo
                $out = [];
                foreach ($newMap as $menuId => $qty) {
                    /** @var Menu|null $m */
                    $m = $em->find(Menu::class, $menuId);
                    if ($m) {
                        $out[] = $this->formatCartItem($m, $qty);
                    }
                }

                return new JsonResponse(['items' => $out]);
            });
        } catch (\Throwable $e) {
            return new JsonResponse(['message' => 'Erro ao sincronizar carrinho'], 500);
        }
    }

    private function formatCartItem(Menu $m, int $qty): array
    {
        return [
            'id' => $m->getId(),
            'name' => $m->getName(),
            'description' => $m->getDescription(),
            'ingredients' => method_exists($m, 'getIngredients') ? $m->getIngredients() : null,
            'allergens' => method_exists($m, 'getAllergens') ? $m->getAllergens() : null,

            'price' => (float)$m->getPrice(),
            'image' => $m->getImageUrl(),

            'stock' => $m->getStock(),
            'reserved' => $m->getReserved(),
            'availableStock' => $m->getAvailableStock(),
            'isSoldOut' => $m->getAvailableStock() <= 0,

            'qty' => max(1, $qty),
        ];
    }
}
