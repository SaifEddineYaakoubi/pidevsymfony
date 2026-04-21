<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProduitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du produit',
                'required' => true,
                'attr' => [
                    'maxlength' => '100',
                    'placeholder' => 'Ex: Tomate rouge, Laitue, Carottes...',
                    'minlength' => '2',
                ],
                'help' => 'Entre 2 et 100 caractères',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type/Catégorie',
                'required' => true,
                'choices' => [
                    'Légume' => 'Légume',
                    'Fruit' => 'Fruit',
                    'Huile' => 'Huile',
                    'Céréale' => 'Céréale',
                    'Herbe aromatique' => 'Herbe aromatique',
                    'Racine/Tubercule' => 'Racine/Tubercule',
                    'Autres' => 'Autres',
                ],
                'placeholder' => 'Sélectionnez une catégorie',
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => 'Catégorie du produit',
            ])
            ->add('unite', ChoiceType::class, [
                'label' => 'Unité de mesure',
                'required' => true,
                'choices' => [
                    'Kilogramme (kg)' => 'kg',
                    'Litre (L)' => 'L',
                ],
                'placeholder' => 'Sélectionnez une unité',
                'attr' => [
                    'class' => 'form-select',
                ],
                'help' => 'Unité utilisée pour ce produit',
            ])
            ->add('prix_unitaire', NumberType::class, [
                'label' => 'Prix unitaire (DT)',
                'required' => true,
                'scale' => 2,
                'attr' => [
                    'min' => '0',
                    'step' => '0.01',
                    'placeholder' => '0.00',
                    'inputmode' => 'decimal',
                ],
                'help' => 'Minimum 0 DT, jusqu\'à 2 décimales',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Produit::class,
        ]);
    }
}
