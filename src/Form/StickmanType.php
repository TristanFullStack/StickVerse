<?php

namespace App\Form;

use App\Entity\Stickman;
use App\Entity\CollectionJeu;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class StickmanType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choixImages = [];
        $cheminProjet = dirname(__DIR__, 2);
        $images = glob($cheminProjet . '/public/images/stickmen/*.png');
        foreach ($images as $image) {
            $nomImage = basename($image);
            $choixImages[$nomImage] = $nomImage;
        }

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
            ->add('image', ChoiceType::class, [
                'label' => 'Image',
                'choices' => $choixImages,
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
            'data_class' => Stickman::class,
        ]);
    }
}
