<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ReinitialiserMotDePasseType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add('nouveauMotDePasse', RepeatedType::class, [
            'type' => PasswordType::class,
            'mapped' => false,
            'first_options' => [
                'label' => 'Nouveau mot de passe',
                'attr' => ['autocomplete' => 'new-password'],
            ],
            'second_options' => [
                'label' => 'Confirmer le nouveau mot de passe',
                'attr' => ['autocomplete' => 'new-password'],
            ],
            'invalid_message' => 'Les deux mots de passe doivent être identiques.',
            'constraints' => [
                new NotBlank(message: 'Saisis un nouveau mot de passe.'),
                new Length(
                    min: 8,
                    minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                    max: 4096,
                ),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'reinitialiser_mot_de_passe',
        ]);
    }
}
