<?php

namespace App\Controller\Api\Auth;

use App\Entity\PasswordResetRequest;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/api/auth', name: 'api_auth_')]
final class PasswordResetController extends AbstractController
{
    #[Route('/forgot-password', name: 'forgot_password', methods: ['POST'])]
    public function forgotPassword(
        Request $request,
        UserRepository $users,
        EntityManagerInterface $em,
        UrlGeneratorInterface $urlGenerator,
        MailerInterface $mailer,
    ): JsonResponse {
        $payload = json_decode($request->getContent() ?: '{}', true);
        $email = strtolower(trim((string)($payload['email'] ?? '')));

        if ($email === '') {
            return $this->json(['message' => 'Adresse e-mail obligatoire.'], 400);
        }

        // mensagem genérica (não revela se o email existe)
        $okMessage = 'Si un compte existe pour cet e-mail, un lien de réinitialisation a été envoyé.';

        $user = $users->findOneBy(['email' => $email]);
        if (!$user) {
            return $this->json(['message' => $okMessage], 200);
        }

        // token random + hash sha256 (nunca salvar token em claro)
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $tokenHash = hash('sha256', $token);

        $expiresAt = (new \DateTimeImmutable())->modify('+1 hour');

        // ✅ tua entity exige 3 args no __construct()
        $resetRequest = new PasswordResetRequest($user, $tokenHash, $expiresAt);

        $em->persist($resetRequest);
        $em->flush();

        // Link WEB que renderiza o twig de reset (ele vai chamar a API depois)
        $resetUrl = $urlGenerator->generate(
            'app_reset_password',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // Email (se MAILER_DSN não estiver configurado, pode causar 500)
        $mail = (new Email())
            ->from('no-reply@vitegourmand.local')
            ->to($user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->html(
                '<p>Pour réinitialiser votre mot de passe, cliquez sur ce lien :</p>' .
                '<p><a href="'.$resetUrl.'">'.$resetUrl.'</a></p>' .
                '<p>Ce lien expire dans 1 heure.</p>'
            );

        try {
            $mailer->send($mail);
        } catch (\Throwable $e) {
            // não quebra o fluxo nem vaza detalhes (em dev, veja var/log/dev.log)
        }

        // Em dev, ajuda muito a testar sem e-mail
        if ($this->getParameter('kernel.environment') === 'dev') {
            return $this->json([
                'message' => $okMessage,
                'debug_reset_url' => $resetUrl,
            ], 200);
        }

        return $this->json(['message' => $okMessage], 200);
    }

    #[Route('/reset-password', name: 'reset_password', methods: ['POST'])]
    public function resetPassword(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
    ): JsonResponse {
        $payload = json_decode($request->getContent() ?: '{}', true);

        $token = (string)($payload['token'] ?? '');
        $newPassword = (string)($payload['password'] ?? '');
        $confirm = (string)($payload['confirm'] ?? '');

        if ($token === '' || $newPassword === '' || $confirm === '') {
            return $this->json(['message' => 'Champs manquants.'], 400);
        }
        if ($newPassword !== $confirm) {
            return $this->json(['message' => 'Les mots de passe ne correspondent pas.'], 400);
        }
        if (mb_strlen($newPassword) < 8) {
            return $this->json(['message' => 'Mot de passe trop court (min 8 caractères).'], 400);
        }

        $tokenHash = hash('sha256', $token);

        /** @var PasswordResetRequest|null $reset */
        $reset = $em->getRepository(PasswordResetRequest::class)->findOneBy(['tokenHash' => $tokenHash]);

        if (!$reset || $reset->isUsed() || $reset->isExpired()) {
            return $this->json(['message' => 'Lien invalide ou expiré.'], 400);
        }

        $user = $reset->getUser();
        if (!$user) {
            return $this->json(['message' => 'Lien invalide ou expiré.'], 400);
        }

        // precisa existir setPassword() no teu User
        $user->setPassword($hasher->hashPassword($user, $newPassword));

        // ✅ usa teu método
        $reset->markUsed();

        $em->flush();

        return $this->json(['message' => 'Mot de passe mis à jour avec succès.'], 200);
    }
}
