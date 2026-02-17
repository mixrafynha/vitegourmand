<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class ApiLoginThrottleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        #[Autowire(service: 'limiter.api_login')]
        private RateLimiterFactory $apiLoginLimiter
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->isMethod('POST')) return;
        if ($request->getPathInfo() !== '/api/auth/login') return;

        $data = json_decode((string) $request->getContent(), true) ?: [];
        $email = strtolower((string) ($data['email'] ?? ''));
        $ip = (string) ($request->getClientIp() ?? 'unknown');
        $key = $ip . '|' . $email;

        $limiter = $this->apiLoginLimiter->create($key);
        $limit = $limiter->consume(1);

        if ($limit->isAccepted()) return;

        $retryAfter = $limit->getRetryAfter();
        $seconds = $retryAfter ? max(1, $retryAfter->getTimestamp() - time()) : 60;

        $event->setResponse(new JsonResponse([
            'message' => 'Muitas tentativas. Tenta novamente mais tarde.',
            'retry_after_seconds' => $seconds,
        ], 429));
    }
}
