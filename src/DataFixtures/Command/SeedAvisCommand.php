<?php

namespace App\Command;

use App\Entity\Avis; // ⚠️ Ajusta se a tua entity tiver outro nome
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:seed-avis',
    description: 'Insere avis de exemplo (apenas para contas verificadas).'
)]
final class SeedAvisCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $users
    ) { parent::__construct(); }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $verifiedUsers = $this->users->createQueryBuilder('u')
            ->andWhere('u.isVerified = true')
            ->getQuery()
            ->getResult();

        if (!$verifiedUsers) {
            $output->writeln('<error>Nenhum utilizador verificado encontrado (u.isVerified = true).</error>');
            return Command::FAILURE;
        }

        $samples = [
            [5, 'Service impeccable, plats délicieux et belle présentation.'],
            [4, 'Ambiance agréable, cuisine fraîche. Très bon moment.'],
            [5, 'Rapide et raffiné. Excellent rapport qualité/prix.'],
            [4, 'Accueil chaleureux, portions parfaites.'],
            [5, 'Une expérience gourmande, tout était parfait.'],
        ];

        $count = 0;
        for ($i = 0; $i < 8; $i++) {
            $user = $verifiedUsers[array_rand($verifiedUsers)];
            [$stars, $message] = $samples[array_rand($samples)];

            $avis = new Avis();
            $avis->setUser($user);
            $avis->setStars($stars);
            $avis->setMessage($message);
            $avis->setCreatedAt(new \DateTimeImmutable('-'.random_int(0, 15).' days'));

            $this->em->persist($avis);
            $count++;
        }

        $this->em->flush();
        $output->writeln("<info>OK: $count avis inseridos.</info>");

        return Command::SUCCESS;
    }
}
