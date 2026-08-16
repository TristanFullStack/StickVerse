<?php

namespace App\Form;

use App\Entity\Equipe;
use App\Entity\Stickman;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EquipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $stickmenDisponibles = $options['stickmen_disponibles'];

        $builder
            ->add('nom')
            ->add('stickmanA', EntityType::class, [
                'class' => Stickman::class,
                'choices' => $stickmenDisponibles,
                'choice_label' => 'nom',
                'label' => 'Stickman A — Équipe X',
            ])
            ->add('stickmanB', EntityType::class, [
                'class' => Stickman::class,
                'choices' => $stickmenDisponibles,
                'choice_label' => 'nom',
                'label' => 'Stickman B — Équipe X',
            ])
            ->add('stickmanC', EntityType::class, [
                'class' => Stickman::class,
                'choices' => $stickmenDisponibles,
                'choice_label' => 'nom',
                'label' => 'Stickman C — Équipe Y',
            ])
            ->add('stickmanD', EntityType::class, [
                'class' => Stickman::class,
                'choices' => $stickmenDisponibles,
                'choice_label' => 'nom',
                'label' => 'Stickman D — Équipe Y',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Equipe::class,
            'stickmen_disponibles' => [],
        ]);
    }
}