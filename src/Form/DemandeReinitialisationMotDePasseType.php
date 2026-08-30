<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

final class DemandeReinitialisationMotDePasseType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add('email', EmailType::class, [
            'mapped' => false,
            'label' => 'Adresse électronique',
            'attr' => ['autocomplete' => 'email'],
            'constraints' => [
                new NotBlank(message: 'Saisis ton adresse électronique.'),
                new Email(message: 'Cette adresse électronique est invalide.'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'demande_reinitialisation_mot_de_passe',
        ]);
    }
}
