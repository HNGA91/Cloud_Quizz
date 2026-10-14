<?php

namespace App\Entity;

use App\Repository\ChoixRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Une réponse proposée pour une question (2, 3 ou 4 par question).
 *
 * Remplace les anciennes colonnes choix1..choix4 + reponse de question_quizz :
 * - le nombre de choix n'est plus figé à 4 ;
 * - la bonne réponse est un booléen (estCorrect) et non plus une copie du texte,
 *   qui pouvait ne correspondre à aucun choix (faute de frappe, espace…) ;
 * - chaque choix peut avoir une image (ex. un drapeau).
 *
 * @ORM\Entity(repositoryClass=ChoixRepository::class)
 */
class Choix
{
    /**
     * @ORM\Id
     *
     * @ORM\GeneratedValue
     *
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * Supprimer une question supprime ses choix (ON DELETE CASCADE côté base).
     *
     * @ORM\ManyToOne(targetEntity=QuestionQuizz::class, inversedBy="choix")
     *
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private ?QuestionQuizz $question = null;

    /**
     * Toujours obligatoire, même avec une image : il sert de texte alternatif
     * (lecteurs d'écran, image qui ne charge pas).
     *
     * @ORM\Column(type="string", length=255)
     */
    #[Assert\NotBlank(message: 'Chaque choix doit avoir un libellé (il sert aussi de texte alternatif à l\'image).')]
    #[Assert\Length(max: 255)]
    private ?string $libelle = null;

    /**
     * Chemin depuis public/, ex. images/jeux/geographie/drapeaux/suede.svg
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    #[Assert\Length(max: 255)]
    #[Assert\Regex(
        pattern: '#^images/[a-z0-9/_-]+\.(svg|webp|png|jpe?g)$#',
        message: 'Chemin attendu : images/… en minuscules, sans accents ni espaces, terminé par .svg, .webp, .png ou .jpg.'
    )]
    private ?string $image = null;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $estCorrect = false;

    /**
     * Ordre d'affichage dans l'administration (1, 2, 3, 4).
     *
     * @ORM\Column(type="smallint", options={"unsigned": true})
     */
    private int $position = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): ?QuestionQuizz
    {
        return $this->question;
    }

    public function setQuestion(?QuestionQuizz $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getLibelle(): ?string
    {
        return $this->libelle;
    }

    public function setLibelle(?string $libelle): self
    {
        $this->libelle = null === $libelle ? null : trim($libelle);

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(?string $image): self
    {
        $image = null === $image ? null : ltrim(trim($image), '/');
        $this->image = '' === $image ? null : $image;

        return $this;
    }

    public function isEstCorrect(): bool
    {
        return $this->estCorrect;
    }

    public function setEstCorrect(bool $estCorrect): self
    {
        $this->estCorrect = $estCorrect;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Vrai pour une ligne laissée vide dans le formulaire (supprimée à l'enregistrement).
     */
    public function estVide(): bool
    {
        return (null === $this->libelle || '' === $this->libelle) && null === $this->image;
    }

    public function __toString(): string
    {
        return (string) $this->libelle;
    }
}
