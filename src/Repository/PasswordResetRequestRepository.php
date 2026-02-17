<?php

namespace App\Repository;

use App\Entity\PasswordResetRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PasswordResetRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetRequest::class);
    }

    public function deleteExpired(): int
    {
        return $this->createQueryBuilder('r')
            ->delete()
            ->where('r.expiresAt <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    public function invalidateAllForUser(User $user): int
    {
        return $this->createQueryBuilder('r')
            ->update()
            ->set('r.usedAt', ':now')
            ->where('r.user = :user')
            ->andWhere('r.usedAt IS NULL')
            ->setParameter('user', $user)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }

    public function findValidByTokenHash(string $tokenHash): ?PasswordResetRequest
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.tokenHash = :h')
            ->andWhere('r.usedAt IS NULL')
            ->andWhere('r.expiresAt > :now')
            ->setParameter('h', $tokenHash)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
