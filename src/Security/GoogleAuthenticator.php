<?php

namespace App\Security;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use League\OAuth2\Client\Provider\GoogleUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class GoogleAuthenticator extends OAuth2Authenticator
{
    public function __construct(
        private ClientRegistry $clients,
        private UserRepository $users,
        private EntityManagerInterface $em,
        private RouterInterface $router,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'connect_google_check';
    }

    public function authenticate(Request $request): SelfValidatingPassport
    {
        $client = $this->clients->getClient('google');
        $accessToken = $this->fetchAccessToken($client);

        /** @var GoogleUser $googleUser */
        $googleUser = $client->fetchUserFromToken($accessToken);

        $email    = strtolower((string) $googleUser->getEmail());
        $googleId = (string) $googleUser->getId();

        if ($email === '' || $googleId === '') {
            throw new AuthenticationException('Google authentication failed (missing data).');
        }

        return new SelfValidatingPassport(
            new UserBadge($email, function () use ($email, $googleId, $googleUser) {

                // 1️⃣ procura por googleId
                $user = $this->users->findOneBy(['googleId' => $googleId]);
                if ($user) {
                    return $user;
                }

                // 2️⃣ procura por email (linka conta existente)
                $user = $this->users->findOneBy(['email' => $email]);
                if ($user) {
                    $user->setGoogleId($googleId);
                    $this->em->flush();
                    return $user;
                }

                // 3️⃣ cria conta nova
                $data = $googleUser->toArray();

                $user = new User();
                $user->setEmail($email);
                $user->setGoogleId($googleId);
                $user->setRoles(['ROLE_USER']);
                $user->setFirstName((string) ($data['given_name'] ?? ''));
                $user->setLastName((string) ($data['family_name'] ?? ''));

                // ✔ se password for nullable → melhor opção
                $user->setPassword(null);

                // ❗ se NÃO for nullable, use isto no lugar:
                // $user->setPassword(bin2hex(random_bytes(32)));

                $this->em->persist($user);
                $this->em->flush();

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(
        Request $request,
        TokenInterface $token,
        string $firewallName
    ): ?Response {
        return new RedirectResponse(
            $this->router->generate('app_home')
        );
    }

    public function onAuthenticationFailure(
        Request $request,
        AuthenticationException $exception
    ): ?Response {
        return new RedirectResponse(
            $this->router->generate('app_login')
        );
    }
}
