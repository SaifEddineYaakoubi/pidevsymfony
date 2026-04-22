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
                'required' => true,
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: Jean Dupont',
                    'minlength' => 3,
                    'maxlength' => 100,
                ],
                'help' => 'Minimum 3 caractères, maximum 100',
            ])
            ->add('contact', TextType::class, [
                'label' => 'Contact (Téléphone ou Email)',
                'required' => true,
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 06 12 34 56 78 ou email@example.com',
                    'minlength' => 8,
                    'maxlength' => 100,
                ],
                'help' => 'Email ou numéro de téléphone valide requis',
            ])
            ->add('adresse', TextType::class, [
                'label' => 'Adresse',
                'required' => true,
                'empty_data' => '',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Ex: 123 Rue de la Paix, 75000 Paris',
                    'minlength' => 3,
                    'maxlength' => 150,
                ],
                'help' => 'Minimum 3 caractères',
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


