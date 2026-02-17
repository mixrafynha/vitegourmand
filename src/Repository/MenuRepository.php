<?php

namespace App\Repository;

use App\Entity\Menu;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class MenuRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Menu::class);
    }

    /**
     * @return array{0: Menu[], 1: int}
     */
    public function search(array $filters, int $page, int $limit, bool $onlyActive = true): array
    {
        $page = max(1, $page);
        $limit = min(50, max(1, $limit));

        // --- Base QB (SEM paginação) ---
        $baseQb = $this->createQueryBuilder('m');

        if ($onlyActive) {
            $baseQb->andWhere('m.isActive = :active')
                ->setParameter('active', true);
        }

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $baseQb->andWhere('LOWER(m.name) LIKE :q OR LOWER(m.description) LIKE :q')
                ->setParameter('q', '%' . mb_strtolower($q) . '%');
        }

        $minPrice = $filters['minPrice'] ?? null;
        if ($minPrice !== null && $minPrice !== '') {
            $baseQb->andWhere('m.price >= :min')
                ->setParameter('min', (string)$minPrice);
        }

        $maxPrice = $filters['maxPrice'] ?? null;
        if ($maxPrice !== null && $maxPrice !== '') {
            $baseQb->andWhere('m.price <= :max')
                ->setParameter('max', (string)$maxPrice);
        }

        // --- Total (SEM LIMIT/OFFSET) ---
        $countQb = clone $baseQb;
        $total = (int) $countQb
            ->select('COUNT(m.id)')
            ->getQuery()
            ->getSingleScalarResult();

        // --- Ordenação + paginação ---
        $sort = (string)($filters['sort'] ?? 'createdAt');
        $order = strtoupper((string)($filters['order'] ?? 'DESC'));
        $order = in_array($order, ['ASC', 'DESC'], true) ? $order : 'DESC';

        $allowedSort = ['createdAt', 'price', 'name'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'createdAt';
        }

        $itemsQb = clone $baseQb;
        $items = $itemsQb
            ->orderBy('m.' . $sort, $order)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return [$items, $total];
    }
}
