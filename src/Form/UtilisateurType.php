<?php
// src/Form/UtilisateurType.php
namespace App\Form;

use App\Entity\Utilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'trim' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez le nom'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le nom est obligatoire.'),
                    new Assert\Length(min: 2, max: 100),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'trim' => true,
                'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez le prénom'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le prénom est obligatoire.'),
                    new Assert\Length(min: 2, max: 100),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'trim' => true,
                // Désactive la validation HTML5 du navigateur (type=email) et laisse Symfony Validator faire.
                'attr' => ['class' => 'form-control', 'placeholder' => 'exemple@email.com', 'type' => 'text', 'inputmode' => 'email'],
                'constraints' => [
                    new Assert\NotBlank(message: 'L\'email est obligatoire.'),
                    new Assert\Email(message: 'Format d\'email invalide.'),
                    new Assert\Length(max: 255),
                ],
            ])
            ->add('role', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Administrateur' => 'admin',
                    'Responsable Stock' => 'responsable_stock',
                    'Agriculteur' => 'agriculteur',
                ],
                'placeholder' => 'Choisir un rôle',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Le rôle est obligatoire.'),
                    new Assert\Choice(choices: ['admin', 'responsable_stock', 'agriculteur'], message: 'Rôle invalide.'),
                ],
            ])
            ->add('mot_de_passe', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => false,
                'mapped' => false,
                'first_options' => [
                    'label' => 'Mot de passe',
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez le mot de passe'],
                ],
                'second_options' => [
                    'label' => 'Confirmer le mot de passe',
                    'attr' => ['class' => 'form-control', 'placeholder' => 'Confirmez le mot de passe'],
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints' => [
                    // Laisser la possibilité d'éditer un utilisateur sans changer le mot de passe,
                    // MAIS si un mot de passe est saisi, on impose au moins 6 caractères.
                    new Assert\Length(min: 0, max: 4096),
                ],
            ])
            ->add('statut', CheckboxType::class, [
                'label' => 'Actif',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Utilisateur::class,
        ]);
    }
}