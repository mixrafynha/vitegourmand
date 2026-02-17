<?php

namespace App\Command;

use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:create-roles',
    description: 'Create default roles (ADMIN, EMPLOYEE, USER)',
)]
class CreateRolesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $roles = [
            Role::ADMIN,
            Role::EMPLOYEE,
            Role::USER,
        ];

        foreach ($roles as $code) {
            $exists = $this->em
                ->getRepository(Role::class)
                ->findOneBy(['code' => $code]);

            if (!$exists) {
                $role = new Role();
                $role->setCode($code);
                $this->em->persist($role);

                $output->writeln("✔ created {$code}");
            } else {
                $output->writeln("• {$code} already exists");
            }
        }

        $this->em->flush();

        return Command::SUCCESS;
    }
}
