<?php

namespace App\DataFixtures;

use App\Entity\Allergen;
use App\Entity\Menu;
use App\Entity\Plat;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $em): void
    {
        // 👑 ADMIN
        $admin = (new User())
            ->setEmail('admin@vitegourmand.test')
            ->setFirstName('Admin')
            ->setLastName('ViteGourmand')
            ->setRoles(['ROLE_ADMIN'])
            ->setIsVerified(true);

        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));
        $em->persist($admin);

        // 👷 EMPLOYEE
        $employee = (new User())
            ->setEmail('employee@vitegourmand.test')
            ->setFirstName('Employee')
            ->setLastName('ViteGourmand')
            ->setRoles(['ROLE_EMPLOYEE'])
            ->setIsVerified(true);

        $employee->setPassword($this->hasher->hashPassword($employee, 'employee123'));
        $em->persist($employee);

        // 👤 USER demo
        $user = (new User())
            ->setEmail('user@vitegourmand.test')
            ->setFirstName('User')
            ->setLastName('ViteGourmand')
            ->setRoles(['ROLE_USER'])
            ->setIsVerified(true);

        $user->setPassword($this->hasher->hashPassword($user, 'user123'));
        $em->persist($user);

        // 🌿 ALLERGENS
        $gluten = (new Allergen())->setName('Gluten');
        $lait   = (new Allergen())->setName('Lait');
        $oeufs  = (new Allergen())->setName('Oeufs');
        $em->persist($gluten); $em->persist($lait); $em->persist($oeufs);

        // 🍽️ MENUS
        $m1 = (new Menu())->setName('Menu Déjeuner')->setDescription('Entrée + Plat + Dessert')->setBasePrice('19.90')->setIsActive(true);
        $m2 = (new Menu())->setName('Menu Vegan')->setDescription('100% végétal')->setBasePrice('21.90')->setIsActive(true);
        $em->persist($m1); $em->persist($m2);

        // 🍛 PLATS
        $p1 = (new Plat())->setMenu($m1)->setName('Poulet rôti')->setDescription('Poulet et pommes')->setPrice('12.00')->setIsActive(true);
        $p2 = (new Plat())->setMenu($m1)->setName('Mousse chocolat')->setDescription('Chocolat noir')->setPrice('5.50')->setIsActive(true)->addAllergen($lait)->addAllergen($oeufs);
        $p3 = (new Plat())->setMenu($m2)->setName('Bowl quinoa')->setDescription('Quinoa + légumes')->setPrice('11.50')->setIsActive(true)->addAllergen($gluten);

        $em->persist($p1); $em->persist($p2); $em->persist($p3);

        $em->flush();
    }
}
