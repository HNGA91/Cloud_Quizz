<?php

namespace App\Form;

use App\Entity\QuestionQuizz;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QuestionQuizzType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('jeux_quizz', null, [
                'label' => 'Quizz',
            ])
            ->add('question', TextType::class, [
                'label' => 'Question',
            ])
            ->add('image', TextType::class, [
                'label' => 'Image de la question (facultatif)',
                'required' => false,
                'help' => 'Chemin depuis public/, ex. images/jeux/geographie/drapeaux/suede.svg',
            ])
            // Le contrôleur prépare toujours 4 lignes (completerChoixVides) ;
            // l'admin en remplit 2, 3 ou 4. Les lignes vides sont ignorées par la
            // validation puis retirées avant l'enregistrement (retirerChoixVides).
            // Résultat : un nombre de choix variable, sans aucun JavaScript.
            // allow_add/allow_delete à false : le nombre de lignes est fixé par le
            // serveur, des lignes ajoutées dans la requête sont ignorées.
            ->add('choix', CollectionType::class, [
                'label' => 'Choix : remplissez 2, 3 ou 4 lignes et cochez la bonne réponse',
                'entry_type' => ChoixType::class,
                'entry_options' => ['label' => false],
                'error_bubbling' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => QuestionQuizz::class,
        ]);
    }
}
