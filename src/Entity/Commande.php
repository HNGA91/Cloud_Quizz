<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CommandeRepository::class)
 */
class Commande
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
    private ?string $reference = null;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="commandes")
     *
     * @ORM\JoinColumn(nullable=false)
     */
    private ?User $user = null;

    /**
     * @var Collection<int, ProduitBoutique>
     */
    private Collection $produits;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @ORM\Column(type="float")
     */
    private ?float $prix = null;

    public function __construct()
    {
        $this->produits = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

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

    /**
     * @return Collection|ProduitBoutique[]
     */
    public function getProduits(): Collection
    {
        return $this->produits;
    }

    public function addProduit(ProduitBoutique $produit): self
    {
        if (!$this->produits->contains($produit)) {
            $this->produits[] = $produit;
        }

        return $this;
    }

    public function removeProduit(ProduitBoutique $produit): self
    {
        $this->produits->removeElement($produit);

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;

        return $this;
    }
}
