<?php

namespace App\Form;

use App\Entity\Equipe;
use App\Entity\Stickman;
use App\Service\ScorePuissanceService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EquipeType extends AbstractType
{
    public function __construct(
        private readonly ScorePuissanceService $scorePuissanceService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $stickmenDisponibles = $options['stickmen_disponibles'];

        $builder
            ->add('nom')
            ->add('stickmanA', EntityType::class, [
                'class' => Stickman::class,
                'choices' => $stickmenDisponibles,
                'choice_label' => fn (Stickman $stickman): string =>
                    $this->libelleStickman($stickman),
                'label' => 'Stickman A — Équipe X',
            ])
            ->add('stickmanB', EntityType::class, [
                'class' => Stickman::class,
                'choices' => $stickmenDisponibles,
                'choice_label' => fn (Stickman $stickman): string =>
                    $this->libelleStickman($stickman),
                'label' => 'Stickman B — Équipe X',
            ])
            ->add('stickmanC', EntityType::class, [
                'class' => Stickman::class,
                'choices' => $stickmenDisponibles,
                'choice_label' => fn (Stickman $stickman): string =>
                    $this->libelleStickman($stickman),
                'label' => 'Stickman C — Équipe Y',
            ])
            ->add('stickmanD', EntityType::class, [
                'class' => Stickman::class,
                'choices' => $stickmenDisponibles,
                'choice_label' => fn (Stickman $stickman): string =>
                    $this->libelleStickman($stickman),
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

    private function libelleStickman(Stickman $stickman): string
    {
        return sprintf(
            '%s — puissance %d',
            $stickman->getNom() ?? 'Stickman',
            $this->scorePuissanceService->calculerStickman($stickman),
        );
    }
}
