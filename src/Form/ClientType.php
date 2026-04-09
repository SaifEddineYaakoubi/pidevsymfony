<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ClientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du client',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: Jean Dupont'],
            ])
            ->add('contact', TextType::class, [
                'label' => 'Contact (Téléphone ou Email)',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 06 12 34 56 78 ou email@example.com'],
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'required' => false,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 123 Rue de la Paix, 75000 Paris'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Client::class,
            // Désactiver complètement la validation HTML5 du navigateur
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}
