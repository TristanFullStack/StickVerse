<?php

namespace App\Form;

use App\Entity\Stickman;
use App\Entity\CollectionJeu;
use App\Service\PassifCombatService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class StickmanType extends AbstractType
{
    public function __construct(
        private readonly PassifCombatService $passifCombatService,
    ) {
    }

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
            ->add('passifs', TextareaType::class, [
                'label' => 'Passifs (JSON, facultatif)',
                'required' => false,
                'empty_data' => '',
                'help' => sprintf(
                    '6 passifs maximum. Types disponibles : %s. Exemple : [{"nom":"Rage","description":"+10 %% ATQ sous 40 %% de PV.","type":"rage","valeur":10}]',
                    implode(', ', $this->passifCombatService->typesDisponibles()),
                ),
                'attr' => [
                    'rows' => 5,
                    'placeholder' => '[]',
                ],
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

        $builder->get('passifs')->addModelTransformer(new CallbackTransformer(
            static function (mixed $passifs): string {
                if (!is_array($passifs) || $passifs === []) {
                    return '';
                }

                return json_encode(
                    $passifs,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
                );
            },
            function (mixed $passifs): array {
                if (!is_string($passifs) || trim($passifs) === '') {
                    return [];
                }

                try {
                    $decoded = json_decode(
                        $passifs,
                        true,
                        512,
                        JSON_THROW_ON_ERROR,
                    );
                } catch (\JsonException $exception) {
                    throw new TransformationFailedException(
                        'Le JSON des passifs est invalide.',
                        0,
                        $exception,
                    );
                }

                if (!is_array($decoded) || array_is_list($decoded) === false) {
                    throw new TransformationFailedException(
                        'Les passifs doivent être un tableau JSON.',
                    );
                }

                if (count($decoded) > PassifCombatService::PASSIFS_MAXIMUM_PAR_CARTE) {
                    throw new TransformationFailedException(
                        sprintf('Une carte ne peut pas contenir plus de %d passifs.', PassifCombatService::PASSIFS_MAXIMUM_PAR_CARTE),
                    );
                }

                $typesDisponibles = array_flip($this->passifCombatService->typesDisponibles());
                foreach ($decoded as $index => $passif) {
                    if (!is_array($passif)) {
                        throw new TransformationFailedException(sprintf('Le passif n°%d doit être un objet JSON.', $index + 1));
                    }

                    $type = $passif['type'] ?? null;
                    if (!is_string($type) || !isset($typesDisponibles[$type])) {
                        throw new TransformationFailedException(sprintf('Le type du passif n°%d est inconnu.', $index + 1));
                    }

                    $valeur = $passif['valeur'] ?? null;
                    if (!is_numeric($valeur) || (float) $valeur < 0 || (float) $valeur > PassifCombatService::BONUS_MAXIMUM) {
                        throw new TransformationFailedException(sprintf('La valeur du passif n°%d doit être comprise entre 0 et %d.', $index + 1, PassifCombatService::BONUS_MAXIMUM));
                    }
                }

                return $decoded;
            },
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Stickman::class,
        ]);
    }
}
