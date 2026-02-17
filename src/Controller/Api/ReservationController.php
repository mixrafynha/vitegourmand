<?php

namespace App\Controller\Api;

use App\Entity\Reservation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/api/reservations')]
class ReservationController extends AbstractController
{
    #[Route('', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $reservation = new Reservation();
        $reservation->setName($data['name']);
        $reservation->setEmail($data['email']);
        $reservation->setPhone($data['phone']);
        $reservation->setDate(new \DateTimeImmutable($data['date']));
        $reservation->setPeople($data['people']);
        $reservation->setMessage($data['message'] ?? null);

        $em->persist($reservation);
        $em->flush();

        $email = (new Email())
            ->from('reservas@restaurant.com')
            ->to('restaurant@email.com')
            ->subject('📅 Nova reserva')
            ->html("
                <h2>Nova Reserva</h2>
                <p><b>Nome:</b> {$reservation->getName()}</p>
                <p><b>Email:</b> {$reservation->getEmail()}</p>
                <p><b>Telefone:</b> {$reservation->getPhone()}</p>
                <p><b>Data:</b> {$reservation->getDate()->format('d/m/Y H:i')}</p>
                <p><b>Pessoas:</b> {$reservation->getPeople()}</p>
                <p>{$reservation->getMessage()}</p>
            ");

        $mailer->send($email);

        return $this->json([
            'message' => 'Reserva enviada com sucesso'
        ], 201);
    }
}
