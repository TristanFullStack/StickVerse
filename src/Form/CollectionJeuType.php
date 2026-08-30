<?php

namespace App\Form;

use App\Entity\CollectionJeu;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CollectionJeuType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, ['label' => 'Nom de la collection'])
            ->add('slug', TextType::class, ['label' => 'Slug (URL)'])
            ->add('description', TextareaType::class, ['label' => 'Description'])
            ->add('saison', IntegerType::class, ['label' => 'Numéro de saison'])
            ->add('statutActif', CheckboxType::class, [
                'label' => 'Collection active ?',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => CollectionJeu::class]);
    }
}
