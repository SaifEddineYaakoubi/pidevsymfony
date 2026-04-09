<?php

namespace App\Form;

use App\Entity\Culture;
use App\Entity\Parcelle;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;

final class CultureType extends AbstractType
{
    /** @var string[] */
    private const ETATS_CROISSANCE = [
        'germination',
        'croissance',
        'floraison',
        'maturite',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Utilisateur|null $user */
        $user = $options['user'] ?? null;

        $builder
            ->add('parcelle', EntityType::class, [
                'class' => Parcelle::class,
                'choice_label' => 'nom',
                'placeholder' => '-- Choisir --',
                'query_builder' => static function (EntityRepository $er) use ($user) {
                    $qb = $er->createQueryBuilder('p')->orderBy('p.nom', 'ASC');
                    if ($user instanceof Utilisateur) {
                        $qb->andWhere('p.id_user = :user')->setParameter('user', $user);
                    }
                    return $qb;
                },
            ])
            ->add('typeCulture', TextType::class, [
                'property_path' => 'type_culture',
            ])
            ->add('datePlantation', DateType::class, [
                'property_path' => 'date_plantation',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('dateRecoltePrevue', DateType::class, [
                'property_path' => 'date_recolte_prevue',
                'widget' => 'single_text',
                'required' => true,
            ])
            ->add('etatCroissance', ChoiceType::class, [
                'property_path' => 'etat_croissance',
                'placeholder' => '-- Choisir --',
                'choices' => [
                    'Germination' => 'germination',
                    'Croissance' => 'croissance',
                    'Floraison' => 'floraison',
                    'Mature' => 'maturite',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Culture::class,
            'user' => null,
        ]);

        $resolver->setAllowedTypes('user', ['null', Utilisateur::class]);
    }
}

