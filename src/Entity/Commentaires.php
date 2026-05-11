<?php

namespace App\Entity;

use App\Repository\CommentairesRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommentairesRepository::class)
 */
class Commentaires
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
     * @ORM\Column(type="text")
     */
    private ?string $contenu = null;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private ?\DateTimeImmutable $created_at = null;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $rgpd = false;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="commentaires")
     *
     * @ORM\JoinColumn(nullable=false)
     */
    private ?User $user = null;

    /**
     * @ORM\ManyToOne(targetEntity=Commentaires::class, inversedBy="reponses")
     */
    private ?self $parent = null;

    /**
     * @ORM\OneToMany(mappedBy="parent", targetEntity=Commentaires::class)
     *
     * @var Collection<int, self>
     */
    private Collection $reponses;

    /**
     * @ORM\ManyToOne(targetEntity=JeuxQuizz::class, inversedBy="commentaires")
     *
     * @ORM\JoinColumn(nullable=false)
     */
    private ?JeuxQuizz $jeuxQuizz = null;

    public function __construct()
    {
        $this->reponses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContenu(): ?string
    {
        return $this->contenu;
    }

    public function setContenu(string $contenu): self
    {
        $this->contenu = $contenu;

        return $this;
    }

    public function __toString(): string
    {
        return $this->contenu;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): self
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function getRgpd(): ?bool
    {
        return $this->rgpd;
    }

    public function setRgpd(bool $rgpd): self
    {
        $this->rgpd = $rgpd;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getReponses(): Collection
    {
        return $this->reponses;
    }

    public function addReponse(self $reponse): self
    {
        if (!$this->reponses->contains($reponse)) {
            $this->reponses[] = $reponse;
            $reponse->setParent($this);
        }

        return $this;
    }

    public function removeReponse(self $reponse): self
    {
        if ($this->reponses->removeElement($reponse)) {
            // set the owning side to null (unless already changed)
            if ($reponse->getParent() === $this) {
                $reponse->setParent(null);
            }
        }

        return $this;
    }

    public function getJeuxQuizz(): ?JeuxQuizz
    {
        return $this->jeuxQuizz;
    }

    public function setJeuxQuizz(?JeuxQuizz $jeuxQuizz): self
    {
        $this->jeuxQuizz = $jeuxQuizz;

        return $this;
    }
}
