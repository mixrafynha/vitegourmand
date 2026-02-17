<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByEmail(string $email): ?User
    {
        $email = mb_strtolower(trim($email));

        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->setParameter('email', $email)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return User[]
     */
    public function findByRole(string $role): array
    {
        // ✅ MySQL JSON_CONTAINS(u.roles, '"ROLE_ADMIN"') = 1
        return $this->createQueryBuilder('u')
            ->andWhere('JSON_CONTAINS(u.roles, :role) = 1')
            ->setParameter('role', json_encode($role, JSON_UNESCAPED_SLASHES))
            ->orderBy('u.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findLatestEmployee(): ?User
    {
        $employees = $this->findByRole('ROLE_EMPLOYEE');
        return $employees[0] ?? null;
    }
}
