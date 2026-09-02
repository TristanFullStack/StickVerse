<?php

namespace App\Form;

use App\Entity\Actualite;
use App\Entity\CollectionJeu;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ActualiteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('titre')->add('slug')->add('contenu', TextareaType::class, ['attr' => ['rows' => 12]])
            ->add('datePublication', DateTimeType::class, ['widget' => 'single_text', 'required' => false])
            ->add('statutActif', CheckboxType::class, ['required' => false])
            ->add('saison', EntityType::class, ['class' => CollectionJeu::class, 'choice_label' => static fn (CollectionJeu $c): string => sprintf('Saison %d — %s', $c->getSaison(), $c->getNom()), 'required' => false, 'placeholder' => 'Toutes les saisons']);
    }
    public function configureOptions(OptionsResolver $resolver): void { $resolver->setDefaults(['data_class' => Actualite::class]); }
}
