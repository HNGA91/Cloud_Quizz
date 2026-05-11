<?php

namespace App\Form;

use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        ->add('email', EmailType::class)
        ->add('nom', TextType::class, [
            'attr' => ['pattern' => '[a-zA-Z]{1,}'],
        ])
        ->add('prenom', TextType::class, [
            'attr' => ['pattern' => '[a-zA-Z]{1,}'],
        ])
        ->add('titre', TextType::class)
        ->add('message', CKEditorType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Configure your form options here
        ]);
    }
}
