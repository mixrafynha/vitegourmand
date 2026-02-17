<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

final class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le prénom est obligatoire.']),
                    new Assert\Length(['max' => 100]),
                ],
                'attr' => ['placeholder' => 'Prénom'],
            ])
            ->add('lastName', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Le nom est obligatoire.']),
                    new Assert\Length(['max' => 100]),
                ],
                'attr' => ['placeholder' => 'Nom'],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'L’email est obligatoire.']),
                    new Assert\Email(['message' => 'Email invalide.']),
                    new Assert\Length(['max' => 180]),
                ],
                'attr' => ['placeholder' => 'Email'],
            ])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,

                // ✅ como você hasha manualmente no controller/service
                'mapped' => false,

                'invalid_message' => 'Les mots de passe ne correspondent pas.',

                'first_options'  => [
                    'attr' => ['placeholder' => 'Mot de passe'],
                    'constraints' => [
                        new Assert\NotBlank(['message' => 'Le mot de passe est obligatoire.']),
                        new Assert\Length([
                            'min' => 8,
                            'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                            'max' => 4096,
                        ]),
                        new Assert\Regex([
                            // “medium”: pelo menos 1 letra e 1 número
                            'pattern' => '/^(?=.*[A-Za-z])(?=.*\d).+$/',
                            'message' => 'Le mot de passe doit contenir au moins une lettre et un chiffre.',
                        ]),
                    ],
                ],
                'second_options' => [
                    'attr' => ['placeholder' => 'Confirmer le mot de passe'],
                    'constraints' => [
                        new Assert\NotBlank(['message' => 'La confirmation du mot de passe est obligatoire.']),
                    ],
                ],
            ]);
    }
}
