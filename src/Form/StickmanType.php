<?php

namespace App\Form;

use App\Entity\Stickman;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StickmanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('slug')
            ->add('description')
            ->add('image')
            ->add('rarete')
            ->add('pv')
            ->add('attaque')
            ->add('defense')
            ->add('statutActif')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stickman::class,
        ]);
    }
}
