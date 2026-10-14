<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=CommandeRepository::class)
 */
#[UniqueEntity('reference', message: 'Cette référence de commande existe déjà.')]
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
     * Unique : deux commandes ne peuvent pas partager la même référence.
     *
     * @ORM\Column(type="string", length=32, unique=true)
     */
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private ?string $reference = null;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="commandes")
     *
     * @ORM\JoinColumn(nullable=false)
     */
    private ?User $user = null;

    /**
     * Les lignes de la commande (produit, quantité, prix au moment de l'achat).
     * cascade persist : persister la commande enregistre aussi ses lignes.
     * orphanRemoval : une ligne retirée de la collection est supprimée en base.
     *
     * @ORM\OneToMany(targetEntity=LigneCommande::class, mappedBy="commande", cascade={"persist"}, orphanRemoval=true)
     *
     * @var Collection<int, LigneCommande>
     */
    private Collection $lignes;

    /**
     * @ORM\Column(type="datetime_immutable")
     */
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * @ORM\Column(type="float")
     */
    private ?float $prix = null;

    /**
     * Identifiant de la session Stripe Checkout ayant payé cette commande.
     * Unique : une même session ne peut pas créer deux commandes (URL rejouée).
     * Nullable : les commandes antérieures à ce champ n'en ont pas.
     *
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     */
    private ?string $stripeSessionId = null;

    public function __construct()
    {
        $this->lignes = new ArrayCollection();
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
     * @return Collection<int, LigneCommande>
     */
    public function getLignes(): Collection
    {
        return $this->lignes;
    }

    public function addLigne(LigneCommande $ligne): self
    {
        if (!$this->lignes->contains($ligne)) {
            $this->lignes[] = $ligne;
            $ligne->setCommande($this);
        }

        return $this;
    }

    public function removeLigne(LigneCommande $ligne): self
    {
        $this->lignes->removeElement($ligne);

        return $this;
    }

    /**
     * Total recalculé à partir des lignes (sert de contrôle face au prix enregistré).
     */
    public function getTotalLignes(): float
    {
        $total = 0.0;

        foreach ($this->lignes as $ligne) {
            $total += $ligne->getSousTotal();
        }

        return $total;
    }

    /**
     * Référence lisible et non devinable, ex. CQ-20261014-3F9A1C.
     */
    public static function genererReference(): string
    {
        return sprintf('CQ-%s-%s', date('Ymd'), strtoupper(bin2hex(random_bytes(3))));
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

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(?string $stripeSessionId): self
    {
        $this->stripeSessionId = $stripeSessionId;

        return $this;
    }
}
