<?php

namespace App\Entity;

use App\Repository\QuestionQuizzRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=QuestionQuizzRepository::class)
 */
class QuestionQuizz
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
     * @ORM\ManyToOne(targetEntity=JeuxQuizz::class)
     *
     * @ORM\JoinColumn(nullable=false)
     */
    private ?JeuxQuizz $jeux_quizz = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $question = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $choix1 = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $choix2 = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $choix3 = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $choix4 = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $reponse = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getJeuxQuizz(): ?JeuxQuizz
    {
        return $this->jeux_quizz;
    }

    public function setJeuxQuizz(?JeuxQuizz $jeux_quizz): self
    {
        $this->jeux_quizz = $jeux_quizz;

        return $this;
    }

    public function getQuestion(): ?string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function getChoix1(): ?string
    {
        return $this->choix1;
    }

    public function setChoix1(string $choix1): self
    {
        $this->choix1 = $choix1;

        return $this;
    }

    public function getChoix2(): ?string
    {
        return $this->choix2;
    }

    public function setChoix2(string $choix2): self
    {
        $this->choix2 = $choix2;

        return $this;
    }

    public function getChoix3(): ?string
    {
        return $this->choix3;
    }

    public function setChoix3(string $choix3): self
    {
        $this->choix3 = $choix3;

        return $this;
    }

    public function getChoix4(): ?string
    {
        return $this->choix4;
    }

    public function setChoix4(string $choix4): self
    {
        $this->choix4 = $choix4;

        return $this;
    }

    public function getReponse(): ?string
    {
        return $this->reponse;
    }

    public function setReponse(string $reponse): self
    {
        $this->reponse = $reponse;

        return $this;
    }
}
