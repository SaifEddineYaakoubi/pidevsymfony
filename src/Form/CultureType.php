<?php

namespace App\Form;

use App\Entity\Culture;
use App\Entity\Parcelle;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

final class CultureType extends AbstractType
{
    /** @var string[] */
    private const ETATS_CROISSANCE = [
        'germination',
        'croissance',
        'floraison',
        'maturite',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Utilisateur|null $user */
        $user = $options['user'] ?? null;

        $builder
            ->add('parcelle', EntityType::class, [
                'class' => Parcelle::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Choisir --',
                'query_builder' => static function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('p')->orderBy('p.nom', 'ASC');
                    if ($user instanceof Utilisateur) {
                        $qb->andWhere('p.id_user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
            ])
            ->add('typeCulture', ChoiceType::class, [
                'property_path' => 'type_culture',
                'placeholder' => '-- Choisir une culture --',
                'choices' => [
                    'Tomate' => 'tomate',
                    'Carotte' => 'carotte',
                    'Courgette' => 'courgette',
                    'Oignon' => 'oignon',
                    'Ail' => 'ail',
                    'Salade' => 'salade',
                    'Haricot' => 'haricot',
                    'Concombre' => 'concombre',
                    'Poivron' => 'poivron',
                    'Piment' => 'piment',
                    'Aubergine' => 'aubergine',
                    'Pomme de terre' => 'pomme de terre',
                    'Blé' => 'blé',
                    'Maïs' => 'maïs',
                    'Pastèque' => 'pastèque',
                    'Melon' => 'melon',
                    'Olivier' => 'olivier',
                    'Agrumes' => 'agrumes',
                ],
                'choice_attr' => function($choice, $key, $value) {
                    $durations = [
                        'tomate' => 80, 'carotte' => 75, 'courgette' => 55, 'oignon' => 115,
                        'ail' => 165, 'salade' => 50, 'haricot' => 60, 'concombre' => 55,
                        'poivron' => 80, 'piment' => 80, 'aubergine' => 70, 'pomme de terre' => 95,
                        'blé' => 165, 'maïs' => 105, 'pastèque' => 90, 'melon' => 80,
                        'olivier' => 365, 'agrumes' => 365
                    ];
                    return ['data-duration' => $durations[$value] ?? 0];
                },
            ])
            ->add('datePlantation', DateType::class, [
                'property_path' => 'date_plantation',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('dateRecoltePrevue', DateType::class, [
                'property_path' => 'date_recolte_prevue',
                'widget' => 'single_text',
                'required' => true,
                'attr' => ['readonly' => true],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Culture::class,
            'user' => null,
        ]);

        $resolver->setAllowedTypes('user', ['null', Utilisateur::class]);
    }
}

