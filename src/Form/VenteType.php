<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Utilisateur;
use App\Entity\Vente;
use App\Entity\Produit;
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
                'required' => true,
                'attr' => ['class' => 'form-control'],
                'data' => new \DateTime(), // Y-7ot el date mta3 el youm wa7dou
            ])
            ->add('idProduit', EntityType::class, [
                'label' => 'Produit',
                'class' => Produit::class,
                'choice_label' => function(?Produit $produit) {
                    if (!$produit) {
                        return '';
                    }
                    // Y-affichi el Esem m3ah el Icon elli zedtha enti!
                    return $produit->getIcon() . ' ' . $produit->getNom();
                },
                'required' => true,
                'attr' => ['class' => 'form-control select2'], // Zid select2 ken t7ebba t-walli recherche
                'placeholder' => 'Sélectionnez un produit',
            ])
            ->add('quantite', NumberType::class, [
                'label' => 'Quantité vendue',
                'scale' => 2,
                'required' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Ex: 10.5'],
            ])
            // Houni el badla el kbiira:
            ->add('montantTotal', NumberType::class, [
                'label' => 'Montant total (Calculé automatiquement)',
                'required' => false, // Ma ghadch obligatoire khater el Controller bech y7esbou
                'attr' => [
                    'class' => 'form-control bg-light',
                    'readonly' => true, // El user ma y-najemch y-iktib fih
                    'placeholder' => 'Sera calculé après validation'
                ],
            ])
            ->add('idClient', EntityType::class, [
                'label' => 'Client',
                'class' => Client::class,
                'choice_label' => 'nom',
                'required' => true,
                'attr' => ['class' => 'form-control'],
            ])
            // Ken t-7eb t-khalli el Admin y-it-7at automatique, na77i 'idUser' mel Form
            // w khalli el Controller y-a3mel $vente->setIdUser($this->getUser())
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vente::class,
            'attr' => ['novalidate' => 'novalidate'],
        ]);
    }
}