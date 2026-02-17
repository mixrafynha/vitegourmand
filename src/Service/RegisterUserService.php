<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegisterUserService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $users,
        private UserPasswordHasherInterface $hasher
    ) {}

    public function register(
        string $email,
        string $firstName,
        string $lastName,
        string $password,
        string $passwordConfirm
    ): User {
        $email = strtolower(trim($email));
        $firstName = trim($firstName);
        $lastName  = trim($lastName);

        if ($email === '' || $firstName === '' || $lastName === '' || $password === '' || $passwordConfirm === '') {
            throw new \InvalidArgumentException('MISSING_FIELDS');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('INVALID_EMAIL');
        }

        if ($password !== $passwordConfirm) {
            throw new \InvalidArgumentException('PASSWORD_MISMATCH');
        }

        $strong = preg_match(
            '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{10,}$/',
            $password
        );

        if (!$strong) {
            throw new \InvalidArgumentException('PASSWORD_WEAK');
        }

        if ($this->users->findOneBy(['email' => $email])) {
            throw new \InvalidArgumentException('EMAIL_IN_USE');
        }

        $user = new User();
        $user
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setRoles(['ROLE_USER'])
            ->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
