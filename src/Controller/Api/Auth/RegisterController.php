<?php

namespace App\Controller\Api\Auth;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
final class RegisterController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $hasher
    ) {}

    #[Route('/api/auth/register', name: 'auth_register', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $raw = $request->getContent() ?? '';

        // JSON inválido -> json_decode retorna null e json_last_error != JSON_ERROR_NONE
        $data = json_decode($raw !== '' ? $raw : '{}', true);

        if (!is_array($data) || json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'INVALID_JSON'], 400);
        }

        $email = mb_strtolower(trim((string)($data['email'] ?? '')));
        $firstName = trim((string)($data['firstName'] ?? ''));
        $lastName = trim((string)($data['lastName'] ?? ''));
        $password = (string)($data['password'] ?? '');
        $passwordConfirm = (string)($data['passwordConfirm'] ?? '');

        // campos obrigatórios
        if ($email === '' || $firstName === '' || $lastName === '' || $password === '' || $passwordConfirm === '') {
            return new JsonResponse(['error' => 'MISSING_FIELDS'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => 'INVALID_EMAIL'], 400);
        }

        if ($password !== $passwordConfirm) {
            return new JsonResponse(['error' => 'PASSWORD_MISMATCH'], 400);
        }

        // password forte
        $strong = (bool) preg_match(
            '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{10,}$/',
            $password
        );

        if (!$strong) {
            return new JsonResponse([
                'error' => 'PASSWORD_WEAK',
                'rules' => 'min 10, 1 uppercase, 1 lowercase, 1 number, 1 symbol',
            ], 400);
        }

        if ($this->users->findOneBy(['email' => $email])) {
            return new JsonResponse(['error' => 'EMAIL_IN_USE'], 409);
        }

        $user = (new User())
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setRoles(['ROLE_USER']);

        // ✅ hash correto: usar o próprio $user
        $user->setPassword($this->hasher->hashPassword($user, $password));

        $this->em->persist($user);
        $this->em->flush();

        // ✅ resposta mínima (menos dados expostos)
        return new JsonResponse([
            'status' => 'ok',
            'id' => $user->getId(),
        ], 201);
    }
}
