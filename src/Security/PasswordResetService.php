<?php

namespace App\Security;

use App\Entity\PasswordResetRequest;
use App\Entity\User;
use App\Repository\PasswordResetRequestRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PasswordResetService
{
    public function __construct(
        private UserRepository $users,
        private PasswordResetRequestRepository $requests,
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private RequestStack $requestStack,
        private string $fromEmail = 'no-reply@vitegourmand.test',
        private int $ttlMinutes = 30,
    ) {}

    /**
     * Sempre retorna true/false mas NUNCA revela se o email existe.
     */
    public function requestReset(string $email): void
    {
        $this->requests->deleteExpired();

        $user = $this->users->findOneBy(['email' => mb_strtolower(trim($email))]);
        if (!$user instanceof User) {
            // resposta “ok” na mesma (anti-enumeração)
            return;
        }

        // invalida tokens antigos
        $this->requests->invalidateAllForUser($user);

        // token seguro
        $token = $this->generateToken();
        $tokenHash = hash('sha256', $token);
        $expiresAt = new \DateTimeImmutable(sprintf('+%d minutes', $this->ttlMinutes));

        $req = new PasswordResetRequest($user, $tokenHash, $expiresAt);
        $this->em->persist($req);
        $this->em->flush();

        $url = $this->buildResetUrl($token);

        $mail = (new Email())
            ->from($this->fromEmail)
            ->to($user->getEmail())
            ->subject('Réinitialisation de mot de passe — ViteGourmand')
            ->text(
                "Bonjour,\n\n".
                "Pour réinitialiser votre mot de passe, utilisez ce lien (valable {$this->ttlMinutes} minutes):\n".
                $url."\n\n".
                "Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.\n"
            );

        $this->mailer->send($mail);
    }

    public function resetPassword(string $token, callable $setNewPasswordHash): bool
    {
        $this->requests->deleteExpired();

        $token = trim($token);
        if ($token === '') {
            return false;
        }

        $tokenHash = hash('sha256', $token);
        $req = $this->requests->findValidByTokenHash($tokenHash);
        if (!$req) {
            return false;
        }

        $user = $req->getUser();
        if (!$user) {
            return false;
        }

        // marca usado (uso único)
        $req->markUsed();
        $this->em->flush();

        // define password (callback para usar PasswordHasher no controller)
        $setNewPasswordHash($user);

        // invalida tudo (opcional extra)
        $this->requests->invalidateAllForUser($user);

        $this->em->flush();
        return true;
    }

    private function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }

    private function buildResetUrl(string $token): string
    {
        $req = $this->requestStack->getCurrentRequest();
        $base = $req ? $req->getSchemeAndHttpHost() : 'http://127.0.0.1:8000';

        // podes trocar para uma página Twig tipo /reset-password?token=...
        return $base.'/reset-password?token='.$token;
    }
}
