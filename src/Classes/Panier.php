<?php

namespace App\Classes;

use App\Entity\ProduitBoutique;
use App\Repository\ProduitBoutiqueRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class Panier
{
    private SessionInterface $session;

    private ProduitBoutiqueRepository $produitBoutiqueRepository;

    public function __construct(
        RequestStack $requestStack,
        ProduitBoutiqueRepository $produitBoutiqueRepository,
    ) {
        $session = $requestStack->getSession();

        if (!$session instanceof SessionInterface) {
            throw new \RuntimeException('Session introuvable.');
        }

        $this->session = $session;
        $this->produitBoutiqueRepository = $produitBoutiqueRepository;
    }

    public function addArticlePanier(int $articleId): void
    {
        $panier = $this->getPanier();

        if (isset($panier[$articleId])) {
            ++$panier[$articleId];
        } else {
            $panier[$articleId] = 1;
        }

        $this->session->set('panier', $panier);
    }

    /**
     * @return array<int, int>
     */
    public function getPanier(): array
    {
        /** @var array<int, int> $panier */
        $panier = $this->session->get('panier', []);

        return $panier;
    }

    public function deletePanier(): void
    {
        $this->session->remove('panier');
    }

    public function deleteArticlePanier(int $id): void
    {
        $panier = $this->getPanier();

        if (isset($panier[$id])) {
            unset($panier[$id]);
        }

        $this->session->set('panier', $panier);
    }

    public function ajoute5(): void
    {
        $panier = $this->getPanier();

        for ($i = 1; $i <= 5; ++$i) {
            $panier[$i] = 1;
        }

        $this->session->set('panier', $panier);
    }

    public function deleteUneQuantite(int $id): void
    {
        $panier = $this->getPanier();

        if (!isset($panier[$id])) {
            return;
        }

        if ($panier[$id] > 1) {
            --$panier[$id];
        } else {
            unset($panier[$id]);
        }

        $this->session->set('panier', $panier);
    }

    /**
     * @return array<int, array{
     *     article: ProduitBoutique,
     *     quantity: int
     * }>
     */
    public function getDetailPanier(): array
    {
        $panier = $this->getPanier();

        $detailPanier = [];

        foreach ($panier as $id => $quantity) {
            $article = $this->produitBoutiqueRepository->find($id);

            if (!$article instanceof ProduitBoutique) {
                continue;
            }

            $detailPanier[] = [
                'article' => $article,
                'quantity' => $quantity,
            ];
        }

        return $detailPanier;
    }

    public function getNombreArticlePanier(): int
    {
        $nombreArticles = 0;

        foreach ($this->getPanier() as $quantity) {
            $nombreArticles += $quantity;
        }

        return $nombreArticles;
    }

    public function getTotalePanier(): float
    {
        $totale = 0.0;

        foreach ($this->getDetailPanier() as $item) {
            $totale += $item['quantity'] * $item['article']->getPrix();
        }

        return $totale;
    }
}