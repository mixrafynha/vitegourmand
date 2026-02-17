<?php

namespace App\Controller\Web;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

final class AuthController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher
    ): Response {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // ✅ Normaliza email (defesa extra)
            if (method_exists($user, 'setEmail') && method_exists($user, 'getEmail')) {
                $user->setEmail((string) $user->getEmail());
            }

            // ✅ Role default
            $user->setRoles(['ROLE_USER']);

            // ✅ Ativar conta por defeito (se existir no User)
            if (method_exists($user, 'setIsActive')) {
                $user->setIsActive(true);
            }

            // ✅ RGPD consent (se existir no User e no form)
            if (method_exists($user, 'setRgpdConsent') && $form->has('rgpdConsent')) {
                $user->setRgpdConsent((bool) $form->get('rgpdConsent')->getData());
            }

            // ✅ Password vem do form (unmapped)
            $plainPassword = (string) ($form->has('password') ? $form->get('password')->getData() : '');
            if ($plainPassword === '') {
                $this->addFlash('error', 'Mot de passe requis.');
                return $this->redirectToRoute('app_register');
            }

            $user->setPassword($hasher->hashPassword($user, $plainPassword));

            try {
                $em->persist($user);
                $em->flush();
            } catch (UniqueConstraintViolationException $e) {
                // ✅ Email já existe (mensagem amigável)
                $this->addFlash('error', 'Cet e-mail est déjà utilisé.');
                return $this->redirectToRoute('app_register');
            }

            $this->addFlash('success', 'Compte créé avec succès.');

            // ✅ PRG: evita resubmit
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
