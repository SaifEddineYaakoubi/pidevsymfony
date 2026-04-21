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
<<<<<<< Updated upstream
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
=======
                'required' => true,
                'attr' => ['min' => '0.01', 'step' => '0.01']
>>>>>>> Stashed changes
            ])
            ->add('date_recolte', DateType::class, [
                'label' => 'Date de récolte',
                'widget' => 'single_text',
<<<<<<< Updated upstream
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\LessThanOrEqual([
                        'value' => 'today',
                        'message' => 'La date de récolte ne peut pas être dans le futur.',
                    ]),
                ],
=======
                'required' => true,
>>>>>>> Stashed changes
            ])
            ->add('qualite', ChoiceType::class, [
                'label' => 'Qualité',
                'required' => true,
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
<<<<<<< Updated upstream
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
=======
                'required' => true,
            ])
            ->add('localisation', TextType::class, [
                'label' => 'Localisation',
                'required' => true,
>>>>>>> Stashed changes
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
