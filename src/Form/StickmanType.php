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
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

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
            ->add('passifs', TextareaType::class, [
                'label' => 'Passifs (JSON, facultatif)',
                'required' => false,
                'empty_data' => '',
                'help' => 'Exemple : [{"nom":"Furie","description":"+10 % ATQ à partir du round 4.","type":"bonus_attaque_pct","valeur":10,"a_partir_round":4}]',
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
            static function (mixed $passifs): array {
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
