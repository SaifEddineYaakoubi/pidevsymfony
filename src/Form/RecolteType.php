<?php

namespace App\Form;

use App\Entity\Recolte;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class RecolteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantite', NumberType::class, [
                'label' => 'Quantité',
            ])
            ->add('date_recolte', DateType::class, [
                'label' => 'Date de récolte',
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('qualite', ChoiceType::class, [
                'label' => 'Qualité',
                'choices' => [
                    'Excellente' => 'excellente',
                    'Bonne' => 'bonne',
                    'Moyenne' => 'moyenne',
                    'Mauvaise' => 'mauvaise',
                ],
            ])
            ->add('type_culture', TextType::class, [
                'label' => 'Type de culture',
            ])
            ->add('localisation', TextType::class, [
                'label' => 'Localisation',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Recolte::class,
        ]);
    }
}
