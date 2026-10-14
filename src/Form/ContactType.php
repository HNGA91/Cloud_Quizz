<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('email', EmailType::class)
        // L'ancien pattern [a-zA-Z] refusait les accents, apostrophes et tirets (ex. « Hervé », « N'Goma »)
        ->add('nom', TextType::class, [
            'constraints' => [new NotBlank(), new Length(['max' => 50])],
        ])
        ->add('prenom', TextType::class, [
            'constraints' => [new NotBlank(), new Length(['max' => 50])],
        ])
        ->add('titre', TextType::class)
        // Texte brut : échappé automatiquement par Twig à l'affichage (pas de HTML)
        ->add('message', TextareaType::class, [
            'attr' => ['rows' => 8, 'style' => 'min-height: 12rem; resize: vertical;'],
            'constraints' => [
                new NotBlank(['message' => 'Veuillez saisir votre message.']),
                new Length(['max' => 3000, 'maxMessage' => 'Votre message ne doit pas dépasser {{ limit }} caractères.']),
            ],
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
