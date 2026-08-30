<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

final class ModifierPseudoType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder->add('pseudo', TextType::class, [
            'label' => 'Nouveau pseudo',
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
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'modifier_pseudo',
        ]);
    }
}
