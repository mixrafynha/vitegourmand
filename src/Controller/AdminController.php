<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('', name: 'admin_dashboard', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        // ⚠️ Importante: "roles" em DB geralmente é JSON/array.
        // findBy(['roles' => ['ROLE_EMPLOYEE']]) pode não funcionar como esperas.
        // Vamos listar tudo e filtrar em PHP para evitar falhas.
        $all = $em->getRepository(User::class)->findBy([], ['id' => 'DESC']);

        $employees = array_values(array_filter($all, static function (User $u) {
            return in_array('ROLE_EMPLOYEE', $u->getRoles(), true);
        }));

        return $this->render('admin/index.html.twig', [
            'employees' => $employees,
        ]);
    }

    #[Route('/employee/create', name: 'admin_employee_create', methods: ['POST'])]
    public function createEmployee(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        // ✅ CSRF
        if (!$this->isCsrfTokenValid('employee_create', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF inválido.');
        }

        $email = trim((string) $request->request->get('email', ''));
        $firstName = trim((string) $request->request->get('first_name', ''));
        $lastName = trim((string) $request->request->get('last_name', ''));
        $password = (string) $request->request->get('password', '');

        if ($email === '' || $password === '') {
            $this->addFlash('error', 'Email e password obrigatórios.');
            return $this->redirectToRoute('admin_dashboard');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'Email inválido.');
            return $this->redirectToRoute('admin_dashboard');
        }

        // password mínimo (ajusta se quiseres mais forte)
        if (mb_strlen($password) < 8) {
            $this->addFlash('error', 'Password deve ter pelo menos 8 caracteres.');
            return $this->redirectToRoute('admin_dashboard');
        }

        // Evita duplicados
        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            $this->addFlash('error', 'Já existe um utilizador com este email.');
            return $this->redirectToRoute('admin_dashboard');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setFirstName($firstName !== '' ? $firstName : null);
        $user->setLastName($lastName !== '' ? $lastName : null);

        // 🔒 Força o role (não confia em input do cliente)
        $user->setRoles(['ROLE_EMPLOYEE']);

        // 🔒 Ativa por padrão (se fizer sentido)
        if (method_exists($user, 'setIsActive')) {
            $user->setIsActive(true);
        }

        $user->setPassword($hasher->hashPassword($user, $password));

        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Empregado criado com sucesso.');
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/employee/{id}/toggle', name: 'admin_employee_toggle', methods: ['POST'])]
    public function toggleEmployee(
        User $user,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        // ✅ CSRF
        if (!$this->isCsrfTokenValid('toggle_user_'.$user->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('CSRF inválido.');
        }

        // ✅ Não permitir mexer em si mesmo (opcional, mas bom)
        $me = $this->getUser();
        if ($me instanceof User && $user->getId() === $me->getId()) {
            $this->addFlash('error', 'Não podes alterar o teu próprio estado.');
            return $this->redirectToRoute('admin_dashboard');
        }

        // ✅ Só permitir toggle de empregados
        if (!in_array('ROLE_EMPLOYEE', $user->getRoles(), true)) {
            $this->addFlash('error', 'Só é permitido alterar contas com ROLE_EMPLOYEE.');
            return $this->redirectToRoute('admin_dashboard');
        }

        // ✅ Toggle com verificação de método existente
        if (!method_exists($user, 'isActive') || !method_exists($user, 'setIsActive')) {
            $this->addFlash('error', 'Campo isActive não está disponível neste utilizador.');
            return $this->redirectToRoute('admin_dashboard');
        }

        $user->setIsActive(!$user->isActive());
        $em->flush();

        $this->addFlash('success', 'Estado do empregado atualizado.');
        return $this->redirectToRoute('admin_dashboard');
    }
}
