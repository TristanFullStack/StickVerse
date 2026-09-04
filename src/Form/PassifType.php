<?php

namespace App\Form;

use App\Entity\Passif;
use App\Service\PassifCombatService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PassifType extends AbstractType
{
    public function __construct(private readonly PassifCombatService $passifCombatService)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom affiché',
                'help' => 'Le nom visible sur la carte et dans les combats.',
            ])
            ->add('slug', TextType::class, [
                'label' => 'Slug',
                'help' => 'Identifiant unique en minuscules (ex. rage, rempart).',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr' => ['rows' => 4],
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de déclenchement',
                'choices' => $this->passifCombatService->definitions(),
                'help' => 'Le moteur applique la règle liée à ce type. Le texte seul ne déclenche aucun code.',
            ])
            ->add('valeur', IntegerType::class, [
                'label' => 'Valeur de l’effet (%)',
                'help' => 'Bonus appliqué en combat, de 0 à 50.',
                'attr' => ['min' => 0, 'max' => PassifCombatService::BONUS_MAXIMUM],
            ])
            ->add('puissance', IntegerType::class, [
                'label' => 'Puissance ajoutée à la carte',
                'help' => 'Valeur fixe ajoutée au score de puissance de chaque carte équipée de ce passif.',
                'attr' => ['min' => 0, 'max' => PassifCombatService::PUISSANCE_MAXIMUM],
            ])
            ->add('aPartirRound', IntegerType::class, [
                'label' => 'Actif à partir du round',
                'required' => false,
                'help' => 'Laisse vide pour un passif actif dès le premier round.',
                'attr' => ['min' => 1],
            ])
            ->add('statutActif', CheckboxType::class, [
                'label' => 'Passif actif',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Passif::class]);
    }
}
