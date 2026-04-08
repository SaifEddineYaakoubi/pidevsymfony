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
use Symfony\Component\Validator\Constraints as Assert;

class RecolteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantite', NumberType::class, [
                'label' => 'Quantité',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
            ])
            ->add('date_recolte', DateType::class, [
                'label' => 'Date de récolte',
                'widget' => 'single_text',
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('qualite', ChoiceType::class, [
                'label' => 'Qualité',
                'choices' => [
                    'Excellente' => 'excellente',
                    'Bonne' => 'bonne',
                    'Moyenne' => 'moyenne',
                    'Mauvaise' => 'mauvaise',
                ],
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('type_culture', TextType::class, [
                'label' => 'Type de culture',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 100]),
                ],
            ])
            ->add('localisation', TextType::class, [
                'label' => 'Localisation',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(['min' => 2, 'max' => 150]),
                ],
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
