<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\CommandeItem;
use App\Repository\CommandeRepository;
use App\Repository\PlatRepository;
use App\Service\StripeFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class CheckoutController extends AbstractController
{
    #[Route('/api/checkout/session', name: 'app_checkout_createsession', methods: ['POST'])]
    public function createSession(
        Request $request,
        Security $security,
        EntityManagerInterface $em,
        PlatRepository $plats,
        CommandeRepository $orders,
        StripeFactory $stripeFactory
    ): JsonResponse {
        $user = $security->getUser();
        if (!$user) return $this->json(['message' => 'Unauthorized'], 401);

        $payload = json_decode($request->getContent(), true) ?? [];
        $items = $payload['items'] ?? null;
        $idempotencyKey = (string)($payload['idempotencyKey'] ?? '');

        if (!is_array($items) || !count($items)) {
            return $this->json(['message' => 'Panier vide'], 400);
        }
        if (strlen($idempotencyKey) < 12) {
            return $this->json(['message' => 'idempotencyKey invalide'], 400);
        }

        $existing = $orders->findOneBy(['idempotencyKey' => $idempotencyKey, 'user' => $user]);
        if ($existing && method_exists($existing, 'getPaymentSessionId') && $existing->getPaymentSessionId()) {
            return $this->json(['message' => 'Checkout déjà créé'], 409);
        }

        // agrega por platId (aceita menuId como fallback)
        $agg = [];
        foreach ($items as $it) {
            $platId = (int)($it['platId'] ?? $it['menuId'] ?? 0);
            $qty = max(1, (int)($it['quantity'] ?? 1));
            if ($platId <= 0) continue;
            $agg[$platId] = ($agg[$platId] ?? 0) + $qty;
        }
        if (!count($agg)) return $this->json(['message' => 'Items invalides'], 400);

        $lineItems = [];
        $totalCents = 0;

        $commande = new Commande();
        $commande->setUser($user);
        $commande->setStatus(Commande::STATUS_PENDING);

        // se você adicionou os campos de pagamento na entity Commande:
        if (method_exists($commande, 'setIdempotencyKey')) $commande->setIdempotencyKey($idempotencyKey);
        if (method_exists($commande, 'setPaymentProvider')) $commande->setPaymentProvider('stripe');
        if (method_exists($commande, 'setPaymentStatus')) $commande->setPaymentStatus('pending');

        foreach ($agg as $platId => $qty) {
            $plat = $plats->find($platId);
            if (!$plat) return $this->json(['message' => "Plat $platId introuvable"], 400);
            if (!$plat->isActive()) return $this->json(['message' => "Plat indisponible: " . $plat->getName()], 400);

            // stock
            if ($plat->getStock() < $qty) {
                return $this->json(['message' => "Stock insuffisant: " . $plat->getName()], 400);
            }

            $unitCents = (int) round(((float)$plat->getPrice()) * 100);
            if ($unitCents <= 0) return $this->json(['message' => 'Prix invalide'], 400);

            $lineTotalCents = $unitCents * $qty;
            $totalCents += $lineTotalCents;

            $ci = new CommandeItem();
            $ci->setCommande($commande);
            $ci->setPlat($plat);
            $ci->setQuantity($qty);
            $ci->setUnitPrice(number_format($unitCents / 100, 2, '.', ''));
            $ci->setLineTotal(number_format($lineTotalCents / 100, 2, '.', ''));

            $em->persist($ci);

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $unitCents,
                    'product_data' => ['name' => $plat->getName()],
                ],
                'quantity' => $qty,
            ];
        }

        $commande->setTotalAmount(number_format($totalCents / 100, 2, '.', ''));

        $em->persist($commande);
        $em->flush();

        $stripe = $stripeFactory->client();
        $appUrl = $_ENV['APP_URL'] ?? $request->getSchemeAndHttpHost();

        try {
            $session = $stripe->checkout->sessions->create(
                [
                    'mode' => 'payment',
                    'line_items' => $lineItems,
                    'success_url' => $appUrl . '/paiement/success?commande_id=' . $commande->getId(),
                    'cancel_url'  => $appUrl . '/paiement/cancel?commande_id=' . $commande->getId(),
                    'metadata' => [
                        'commande_id' => (string)$commande->getId(),
                        'user_id' => (string)$user->getId(),
                    ],
                ],
                [
                    'idempotency_key' => 'checkout_' . $idempotencyKey,
                ]
            );
        } catch (\Throwable $e) {
            // logando no dev ajuda muito
            return $this->json(['message' => 'Stripe error: ' . $e->getMessage()], 500);
        }

        if (method_exists($commande, 'setPaymentSessionId')) {
            $commande->setPaymentSessionId($session->id);
            $em->flush();
        }

        return $this->json(['checkoutUrl' => $session->url, 'commandeId' => $commande->getId()]);
    }
}
