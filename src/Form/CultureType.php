<?php

namespace App\Form;

use App\Entity\Culture;
use App\Entity\Parcelle;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        $builder
            ->add('parcelle', EntityType::class, [
                'class' => Parcelle::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Choisir --',
            ])
            ->add('typeCulture', TextType::class, [
                'property_path' => 'type_culture',
            ])
            ->add('datePlantation', DateType::class, [
                'property_path' => 'date_plantation',
                'widget' => 'single_text',
            ])
            ->add('dateRecoltePrevue', DateType::class, [
                'property_path' => 'date_recolte_prevue',
                'widget' => 'single_text',
            ])
            ->add('etatCroissance', ChoiceType::class, [
                'property_path' => 'etat_croissance',
                'choices' => [
                    'Germination' => 'germination',
                    'Croissance' => 'croissance',
                    'Floraison' => 'floraison',
                    'Mature' => 'maturite',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Culture::class,
        ]);
    }
}

