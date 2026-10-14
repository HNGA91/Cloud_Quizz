<?php

namespace App\Form;

use App\Entity\Commentaires;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CommentairesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // Texte brut : échappé automatiquement par Twig à l'affichage (pas de HTML)
            ->add('contenu', TextareaType::class, [
                'label' => 'Votre commentaire :',
                'attr' => ['rows' => 5, 'style' => 'min-height: 9rem; resize: vertical;'],
                'constraints' => [
                    new NotBlank(['message' => 'Votre commentaire est vide.']),
                    new Length(['max' => 2000, 'maxMessage' => 'Votre commentaire ne doit pas dépasser {{ limit }} caractères.']),
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
