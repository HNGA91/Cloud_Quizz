<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'attr' => ['pattern' => '[a-zA-Z]{1,}'],
            ])
            ->add('prenom', TextType::class, [
                'attr' => ['pattern' => '[a-zA-Z]{1,}'],
            ])
            ->add('pseudonyme', TextType::class, [
                'attr' => ['pattern' => '[A-Za-z0-9]{1,}'],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new NotBlank([
                        'message' => 'Veuillez saisir une adresse email',
                    ]),
                ],
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                ],
            ])
            ->add('roles')
            ->add('isVerified')

            ->get('roles')
            ->addModelTransformer(new CallbackTransformer(
                function ($rolesArray) {
                    // transform the array to a string
                    return count($rolesArray) ? $rolesArray[0] : null;
                },

                function ($rolesString) {
                    // transform the string back to an array
                    return [$rolesString];
                },
            ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
