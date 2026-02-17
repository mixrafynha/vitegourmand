<?php

namespace App\Service;

use App\Entity\PasswordResetRequest;
use App\Entity\User;
use App\Repository\PasswordResetRequestRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class PasswordResetService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PasswordResetRequestRepository $repo,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly MailerInterface $mailer,
        private readonly string $frontUrl, // injeta via services.yaml
    ) {}

    /** Gera request + envia email; não revela se user existe */
    public function createAndSend(User $user, ?string $ip, ?string $userAgent): void
    {
        // Invalida tokens anteriores (opcional mas recomendado)
        $qb = $this->em->createQueryBuilder()
            ->update(PasswordResetRequest::class, 'r')
            ->set('r.usedAt', ':now')
            ->where('r.user = :u')
            ->andWhere('r.usedAt IS NULL')
            ->setParameter('u', $user)
            ->setParameter('now', new \DateTimeImmutable());
        $qb->getQuery()->execute();

        $rawToken = $this->generateToken();
        $tokenHash = hash('sha256', $rawToken);

        $now = new \DateTimeImmutable();
        $expires = $now->modify('+15 minutes');

        $req = (new PasswordResetRequest())
            ->setUser($user)
            ->setTokenHash($tokenHash)
            ->setCreatedAt($now)
            ->setExpiresAt($expires)
            ->setIp($ip)
            ->setUserAgent($userAgent);

        $this->em->persist($req);
        $this->em->flush();

        $resetLink = rtrim($this->frontUrl, '/') . '/reset-password?token=' . urlencode($rawToken);

        $email = (new Email())
            ->from('no-reply@vite-gourmand.local')
            ->to($user->getEmail())
            ->subject('Réinitialisation de mot de passe')
            ->text("Bonjour,\n\nCliquez sur ce lien pour réinitialiser votre mot de passe:\n$resetLink\n\nCe lien expire dans 15 minutes.\n");

        $this->mailer->send($email);
    }

    public function resetPassword(string $rawToken, User $user, string $newPlainPassword): void
    {
        $user->setPassword($this->hasher->hashPassword($user, $newPlainPassword));
        $this->em->flush();
    }

    public function consumeToken(string $rawToken): ?PasswordResetRequest
    {
        $hash = hash('sha256', $rawToken);
        $now = new \DateTimeImmutable();

        $req = $this->repo->findUsableByTokenHash($hash, $now);
        if (!$req) {
            return null;
        }

        $req->setUsedAt($now);
        $this->em->flush();

        return $req;
    }

    private function generateToken(): string
    {
        // base64url sem = + / (bom para URL)
        $bytes = random_bytes(32);
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }
}
