<?php

namespace App\Form;

use App\Entity\Caisse;
use App\Entity\CollectionJeu;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CaisseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('slug')
            ->add('description')
            ->add('image')
            ->add('prix')
            ->add('statutActif')
            ->add('collectionJeu', EntityType::class, [
                'class' => CollectionJeu::class,
                'choice_label' => static fn (CollectionJeu $collection): string => sprintf(
                    'Saison %d — %s',
                    $collection->getSaison(),
                    $collection->getNom(),
                ),
                'label' => 'Collection',
                'required' => false,
                'placeholder' => 'Aucune collection',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Caisse::class,
        ]);
    }
}
