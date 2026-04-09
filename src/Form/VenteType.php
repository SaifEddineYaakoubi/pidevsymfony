<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Utilisateur;
use App\Entity\Vente;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\DateType as FormDateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VenteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dateVente', FormDateType::class, [
                'label' => 'Date de vente',
                'widget' => 'single_text',
                'html5' => true,
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('montantTotal', NumberType::class, [
                'label' => 'Montant total',
                'scale' => 2,
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => '0.00'],
            ])
            ->add('idClient', EntityType::class, [
                'label' => 'Client',
                'class' => Client::class,
                'choice_label' => 'nom',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('idUser', EntityType::class, [
                'label' => 'Utilisateur',
                'class' => Utilisateur::class,
                'choice_label' => 'nom',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vente::class,
            // Désactiver complètement la validation HTML5 du navigateur
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
