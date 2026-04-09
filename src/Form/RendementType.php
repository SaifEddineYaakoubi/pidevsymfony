<?php

namespace App\Form;

use App\Entity\Rendement;
use App\Entity\Recolte;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Validator\Constraints as Assert;

class RendementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id_recolte', EntityType::class, [
                'class' => Recolte::class,
                'choice_label' => function(Recolte $recolte) {
                    return sprintf('#%d - %s (%s)', $recolte->getIdRecolte(), $recolte->getTypeCulture(), $recolte->getLocalisation());
                },
                'label' => 'Récolte',
                'placeholder' => 'Sélectionnez une récolte',
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('surface_exploitee', NumberType::class, [
                'label' => 'Surface Exploitée (m²)',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
            ])
            ->add('quantite_totale', NumberType::class, [
                'label' => 'Quantité Totale Récoltée (kg)',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Positive(),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Rendement::class,
        ]);
    }
}
