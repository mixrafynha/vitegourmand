<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StripeWebhookController
{
    #[Route('/api/webhooks/stripe', name: 'app_stripewebhook_handle', methods: ['POST'])]
    public function handle(Request $request, LoggerInterface $logger): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null;

        if (!$secret) {
            $logger->error('STRIPE_WEBHOOK_SECRET não definido.');
            return new Response('Webhook secret missing', 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\UnexpectedValueException $e) {
            $logger->warning('Payload inválido no webhook Stripe: '.$e->getMessage());
            return new Response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            $logger->warning('Assinatura inválida no webhook Stripe: '.$e->getMessage());
            return new Response('Invalid signature', 400);
        } catch (\Throwable $e) {
            $logger->error('Erro inesperado ao validar webhook Stripe: '.$e->getMessage());
            return new Response('Webhook error', 500);
        }

        try {
            switch ($event->type) {
                case 'checkout.session.completed':
                    // $session = $event->data->object;
                    // TODO: sua lógica aqui
                    break;

                default:
                    // Ignora eventos que você não usa
                    break;
            }
        } catch (\Throwable $e) {
            $logger->error('Erro processando evento Stripe '.$event->type.': '.$e->getMessage());
            return new Response('Handler error', 500);
        }

        return new Response('ok', 200);
    }
}
