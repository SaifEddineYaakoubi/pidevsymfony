<?php

namespace App\Form;

use App\Entity\Stock;
use App\Entity\Produit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quantite', NumberType::class, [
                'label' => 'Quantité',
                'required' => true,
                'scale' => 2,
            ])
            ->add('date_entree', DateType::class, [
                'label' => 'Date entrée',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('date_expiration', DateType::class, [
                'label' => 'Date expiration',
                'required' => true,
                'widget' => 'single_text',
            ])
            ->add('id_produit', EntityType::class, [
                'class' => Produit::class,
                'choice_label' => 'nom',
                'label' => 'Produit',
                'required' => true,
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
            'data_class' => Stock::class,
        ]);
    }
}
