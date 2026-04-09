<?php

namespace App\Form;

use App\Entity\Produit;
use Symfony\Component\Form\AbstractType;
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
            ])
            ->add('type', TextType::class, [
                'label' => 'Type',
                'required' => true,
            ])
            ->add('unite', TextType::class, [
                'label' => 'Unité',
                'required' => true,
            ])
            ->add('prix_unitaire', NumberType::class, [
                'label' => 'Prix unitaire (DT)',
                'required' => true,
                'scale' => 2,
            ])
            ->add('id_user', NumberType::class, [
                'label' => 'ID utilisateur',
                'required' => true,
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
