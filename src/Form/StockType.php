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
                'attr' => [
                    'min' => '0',
                    'step' => '0.01',
                    'placeholder' => 'Entrez la quantité (ex: 50.00)',
                    'inputmode' => 'decimal',
                ],
                'help' => 'La quantité doit être supérieure ou égale à 0',
            ])
            ->add('date_entree', DateType::class, [
                'label' => 'Date d\'entrée',
                'required' => true,
                'widget' => 'single_text',
                'attr' => [
                    'type' => 'date',
                    'placeholder' => 'YYYY-MM-DD',
                ],
                'help' => 'Sélectionnez la date d\'entrée du stock',
            ])
            ->add('date_expiration', DateType::class, [
                'label' => 'Date d\'expiration',
                'required' => true,
                'widget' => 'single_text',
                'attr' => [
                    'type' => 'date',
                    'placeholder' => 'YYYY-MM-DD',
                ],
                'help' => 'Doit être supérieure ou égale à la date d\'entrée',
            ])
            ->add('id_produit', EntityType::class, [
                'class' => Produit::class,
                'choice_label' => 'nom',
                'label' => 'Produit',
                'required' => true,
                'placeholder' => '-- Sélectionnez un produit --',
                'error_bubbling' => false,
                'help' => 'Choisissez le produit à stocker',
            ])
            ->add('id_user', NumberType::class, [
                'label' => 'ID utilisateur',
                'required' => true,
                'attr' => [
                    'min' => '1',
                    'placeholder' => 'ID utilisateur',
                    'inputmode' => 'numeric',
                ],
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
