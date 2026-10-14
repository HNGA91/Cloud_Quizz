<?php

namespace App\Entity;

use App\Repository\LigneCommandeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Une ligne d'une commande : quel produit, combien, et à quel prix.
 *
 * Le titre et le prix unitaire sont copiés au moment de l'achat (« instantané ») :
 * si l'admin renomme un produit, change son prix ou le supprime plus tard,
 * l'historique des commandes reste exact.
 *
 * @ORM\Entity(repositoryClass=LigneCommandeRepository::class)
 */
class LigneCommande
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
     * Supprimer une commande supprime ses lignes (ON DELETE CASCADE côté base).
     *
     * @ORM\ManyToOne(targetEntity=Commande::class, inversedBy="lignes")
     *
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     */
    private ?Commande $commande = null;

    /**
     * Nullable : si le produit est supprimé de la boutique, la ligne reste
     * (avec son titre et son prix copiés) et le lien passe à NULL.
     *
     * @ORM\ManyToOne(targetEntity=ProduitBoutique::class)
     *
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private ?ProduitBoutique $produit = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private ?string $titre = null;

    /**
     * @ORM\Column(type="float")
     */
    private ?float $prixUnitaire = null;

    /**
     * @ORM\Column(type="smallint", options={"unsigned": true})
     */
    private int $quantite = 1;

    public static function depuisProduit(ProduitBoutique $produit, int $quantite): self
    {
        $ligne = new self();
        $ligne->produit = $produit;
        $ligne->titre = (string) $produit->getTitre();
        $ligne->prixUnitaire = (float) $produit->getPrix();
        $ligne->quantite = $quantite;

        return $ligne;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommande(): ?Commande
    {
        return $this->commande;
    }

    public function setCommande(?Commande $commande): self
    {
        $this->commande = $commande;

        return $this;
    }

    public function getProduit(): ?ProduitBoutique
    {
        return $this->produit;
    }

    public function setProduit(?ProduitBoutique $produit): self
    {
        $this->produit = $produit;

        return $this;
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

    public function getPrixUnitaire(): ?float
    {
        return $this->prixUnitaire;
    }

    public function setPrixUnitaire(float $prixUnitaire): self
    {
        $this->prixUnitaire = $prixUnitaire;

        return $this;
    }

    public function getQuantite(): int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getSousTotal(): float
    {
        return $this->quantite * (float) $this->prixUnitaire;
    }
}
