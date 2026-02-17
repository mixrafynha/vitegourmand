<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $admin = (new User())
            ->setEmail('admin@restaurant.test')
            ->setFirstName('Admin')
            ->setLastName('Restaurant')
            ->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'Admin@12345!'));
        $manager->persist($admin);

        $employee = (new User())
            ->setEmail('employee@restaurant.test')
            ->setFirstName('Employee')
            ->setLastName('Restaurant')
            ->setRoles(['ROLE_EMPLOYEE']);
        $employee->setPassword($this->hasher->hashPassword($employee, 'Employee@12345!'));
        $manager->persist($employee);

        $manager->flush();
    }
}
