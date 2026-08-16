<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PlanCombatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $slots = [
            'Slot A' => 'A',
            'Slot B' => 'B',
            'Slot C' => 'C',
            'Slot D' => 'D',
        ];

        $builder
            ->add('cibleAttaqueX', ChoiceType::class, [
                'label' => 'Cible attaquée par l’équipe X (A+B)',
                'choices' => $slots,
                'placeholder' => 'Choisir une cible',
            ])
            ->add('cibleAttaqueY', ChoiceType::class, [
                'label' => 'Cible attaquée par l’équipe Y (C+D)',
                'choices' => $slots,
                'placeholder' => 'Choisir une cible',
            ])
            ->add('cibleDefenseX', ChoiceType::class, [
                'label' => 'Slot protégé par l’équipe X (A+B)',
                'choices' => $slots,
                'placeholder' => 'Choisir un slot',
            ])
            ->add('cibleDefenseY', ChoiceType::class, [
                'label' => 'Slot protégé par l’équipe Y (C+D)',
                'choices' => $slots,
                'placeholder' => 'Choisir un slot',
            ])
            ->add('valider', SubmitType::class, [
                'label' => 'Valider mon plan',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}