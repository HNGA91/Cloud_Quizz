<?php

namespace App\Entity;

use App\Repository\ThemeQuizzRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ThemeQuizzRepository::class)
 */
class ThemeQuizz
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
     * @ORM\Column(type="string", length=255)
     */
    private ?string $titre = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $image = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $image_alt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getImageAlt(): ?string
    {
        return $this->image_alt;
    }

    public function setImageAlt(string $image_alt): self
    {
        $this->image_alt = $image_alt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->titre;
    }
}
