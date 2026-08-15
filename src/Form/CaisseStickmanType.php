<?php

namespace App\Form;

use App\Entity\Caisse;
use App\Entity\CaisseStickman;
use App\Entity\Stickman;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CaisseStickmanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('poids')
            ->add('caisse', EntityType::class, [
                'class' => Caisse::class,
                'choice_label' => 'nom  ',
            ])
            ->add('stickman', EntityType::class, [
                'class' => Stickman::class,
                'choice_label' => 'nom',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CaisseStickman::class,
        ]);
    }
}
