<?php

namespace App\Form;

use App\Entity\Commentaires;
use FOS\CKEditorBundle\Form\Type\CKEditorType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CommentairesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('contenu', CKEditorType::class, [
                'label' => 'Votre commentaire :',
                'attr' => [
                    'form-control',
                ],
            ])
            ->add('rgpd', CheckboxType::class, [
                'label' => 'J\'accepte les conditions d\'utilisation',
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('parent', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('envoyer', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Commentaires::class,
        ]);
    }
}
