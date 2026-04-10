<?php

namespace App\Form;

use App\Entity\Rendement;
use App\Entity\Recolte;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\NumberType;

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
            ])
            ->add('surface_exploitee', NumberType::class, [
                'label' => 'Surface Exploitée (m²)',
            ])
            ->add('quantite_totale', NumberType::class, [
                'label' => 'Quantité Totale Récoltée (kg)',
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
