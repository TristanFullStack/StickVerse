<?php

namespace App\Form;

use App\Entity\CollectionJeu;
use App\Entity\Passif;
use App\Entity\Stickman;
use App\Repository\PassifRepository;
use App\Service\PassifAffectationService;
use App\Service\PassifCombatService;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormError;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Count;

final class StickmanType extends AbstractType
{
    public function __construct(
        private readonly PassifRepository $passifRepository,
        private readonly PassifAffectationService $passifAffectationService,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choixImages = [];
        $images = glob(dirname(__DIR__, 2) . '/public/images/stickmen/*.png') ?: [];
        foreach ($images as $image) {
            $nomImage = basename($image);
            $choixImages[$nomImage] = $nomImage;
        }

        $stickman = $options['data'] instanceof Stickman ? $options['data'] : new Stickman();
        $passifsDisponibles = $this->passifRepository->findBy([], ['nom' => 'ASC']);

        $builder
            ->add('nom', TextType::class, ['label' => 'Nom du Stickman'])
            ->add('slug', TextType::class, ['label' => 'Slug (URL)'])
            ->add('description', TextareaType::class, ['label' => 'Description'])
            ->add('image', ChoiceType::class, ['label' => 'Image', 'choices' => $choixImages])
            ->add('rarete', IntegerType::class, ['label' => 'Rareté (1-5)'])
            ->add('pv', IntegerType::class, ['label' => 'Points de vie'])
            ->add('attaque', IntegerType::class, ['label' => 'Attaque'])
            ->add('defense', IntegerType::class, ['label' => 'Défense'])
            ->add('passifsSelection', EntityType::class, [
                'class' => Passif::class,
                'mapped' => false,
                'multiple' => true,
                'required' => false,
                'choices' => $passifsDisponibles,
                'data' => $this->passifRepository->trouverPourStickman($stickman),
                'choice_label' => static fn (Passif $passif): string => sprintf(
                    '%s (+%d puissance%s)',
                    $passif->getNom(),
                    $passif->getPuissance(),
                    $passif->isStatutActif() ? '' : ', inactif',
                ),
                'label' => 'Passifs (0 à 6)',
                'help' => 'Sélectionne jusqu’à six passifs. Leur valeur et leur puissance se règlent dans le CRUD Passifs.',
                'constraints' => [
                    new Count(
                        max: PassifCombatService::PASSIFS_MAXIMUM_PAR_CARTE,
                        maxMessage: 'Une carte ne peut pas contenir plus de {{ limit }} passifs.',
                    ),
                ],
                'attr' => ['size' => min(12, max(4, count($passifsDisponibles)))],
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
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $stickman = $event->getData();
            if (!$stickman instanceof Stickman) {
                return;
            }

            $selection = $event->getForm()->get('passifsSelection')->getData();
            $selection = is_array($selection) ? $selection : [];
            if ($stickman->getRarete() === 1 && $selection !== []) {
                $event->getForm()->get('passifsSelection')->addError(
                    new FormError('Les cartes R1 doivent rester sans passif.'),
                );
                $selection = [];
            }
            $stickman->setPassifs($this->passifAffectationService->snapshotsDepuis($selection));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Stickman::class]);
    }
}
