<?php

namespace App\Form;

use App\Entity\Stickman;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

class StickmanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du Stickman',
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug (URL)',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
            ])
            ->add('image', TextType::class, [
                'label' => 'Image',
            ])
            ->add('rarete', IntegerType::class, [
                'label' => 'Rareté (1-5)',
            ])
            ->add('pv', IntegerType::class, [
                'label' => 'Points de vie',
            ])
            ->add('attaque', IntegerType::class, [
                'label' => 'Attaque',
            ])
            ->add('defense', IntegerType::class, [
                'label' => 'Défense',
            ])
            ->add('statutActif', CheckboxType::class, [
                'label' => 'Actif ?',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stickman::class,
        ]);
    }
}
