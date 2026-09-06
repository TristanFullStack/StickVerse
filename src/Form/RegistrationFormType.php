<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo public',
                'attr' => [
                    'autocomplete' => 'nickname',
                    'maxlength' => 24,
                ],
                'help' => 'De 3 à 24 lettres, chiffres, tirets ou tirets bas.',
                'constraints' => [
                    new NotBlank(message: 'Choisis un pseudo.'),
                    new Length(
                        min: 3,
                        max: 24,
                        minMessage: 'Le pseudo doit contenir au moins {{ limit }} caractères.',
                        maxMessage: 'Le pseudo ne peut pas dépasser {{ limit }} caractères.',
                    ),
                    new Regex(
                        pattern: '/^[\p{L}\p{N}_-]+$/u',
                        message: 'Le pseudo accepte seulement les lettres, chiffres, tirets et tirets bas.',
                    ),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
                'attr' => [
                    'autocomplete' => 'email',
                    'inputmode' => 'email',
                    'maxlength' => 180,
                    'placeholder' => 'toi@exemple.com',
                ],
                'help' => 'Elle servira à confirmer ton compte et à récupérer ton accès.',
                'constraints' => [
                    new NotBlank(message: 'Indique ton adresse e-mail.'),
                    new Email(message: 'Saisis une adresse e-mail valide.'),
                    new Length(
                        max: 180,
                        maxMessage: 'Cette adresse e-mail est trop longue.',
                    ),
                ],
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'mapped' => false,
                'constraints' => [
                    new IsTrue(
                        message: 'Accepte les conditions d’utilisation pour continuer.',
                    ),
                ],
            ])
            ->add('connexionAutomatique', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('plainPassword', PasswordType::class, [
                // instead of being set onto the object directly,
                // this is read and encoded in the controller
                'mapped' => false,
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(
                        message: 'Choisis un mot de passe.',
                    ),
                    new Length(
                        min: 8,
                        minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                        // max length allowed by Symfony for security reasons
                        max: 4096,
                    ),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
