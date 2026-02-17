<?php

namespace App\DataFixtures;

use App\Entity\Avis; // <-- ajusta se o nome da entity for outro
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

final class AvisFixtures extends Fixture
{
    public function __construct(private UserRepository $users) {}

    public function load(ObjectManager $manager): void
    {
        // ✅ busca users verificados (isVerified = true)
        $verifiedUsers = $this->users->createQueryBuilder('u')
            ->andWhere('u.isVerified = true')
            ->getQuery()
            ->getResult();

        if (count($verifiedUsers) === 0) {
            // sem users verificados → não cria avis
            return;
        }

        $samples = [
            ['stars' => 5, 'message' => 'Service impeccable, plats délicieux et belle présentation.'],
            ['stars' => 4, 'message' => 'Ambiance agréable, cuisine fraîche. Très bon moment.'],
            ['stars' => 5, 'message' => 'Rapide et raffiné. Excellent rapport qualité/prix.'],
            ['stars' => 4, 'message' => 'Accueil chaleureux, portions parfaites. On recommande !'],
            ['stars' => 5, 'message' => 'Une expérience gourmande, tout était parfait.'],
            ['stars' => 4, 'message' => 'Très bon, surtout les desserts.'],
            ['stars' => 5, 'message' => 'Qualité au top, service rapide et pro.'],
            ['stars' => 4, 'message' => 'Très bon restaurant, je reviendrai.'],
        ];

        // ✅ cria avis: 1 ou 2 por user verificado (até 12 no total)
        $max = min(12, count($verifiedUsers) * 2);
        $i = 0;

        while ($i < $max) {
            $user = $verifiedUsers[array_rand($verifiedUsers)];
            $s = $samples[array_rand($samples)];

            $avis = new Avis();
            $avis->setUser($user);
            $avis->setStars($s['stars']);
            $avis->setMessage($s['message']);
            $avis->setCreatedAt(new \DateTimeImmutable('-'.random_int(0, 20).' days'));

            $manager->persist($avis);
            $i++;
        }

        $manager->flush();
    }
}
