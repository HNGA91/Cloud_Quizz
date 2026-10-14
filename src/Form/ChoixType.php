<?php

namespace App\Form;

use App\Entity\Choix;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Une ligne « choix » dans le formulaire d'une question.
 */
class ChoixType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
                'required' => false, // une ligne peut rester vide (question à 2 ou 3 choix)
                'empty_data' => '',
            ])
            ->add('image', TextType::class, [
                'label' => 'Image (facultatif)',
                'required' => false,
                'attr' => ['placeholder' => 'images/jeux/geographie/drapeaux/suede.svg'],
            ])
            ->add('estCorrect', CheckboxType::class, [
                'label' => 'Bonne réponse',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Choix::class,
        ]);
    }
}
