<?php

namespace App\Controller;

use App\Classes\Panier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PanierController extends AbstractController
{
    public function __construct(
        private readonly Panier $panier,
    ) {
    }

    #[Route('/panier', name: 'panier')]
    public function index(): Response
    {
        return $this->render('panier/index.html.twig', [
            // je get le panier par la méthode getPanier de l'obget $panier
            'Panier' => $this->panier->getDetailPanier(),
            'nombre_article' => $this->panier->getNombreArticlePanier(),
            'totale_panier' => $this->panier->getTotalePanier(),
        ]);
    }

    #[Route('/ajouter-panier/{id}', name: 'add_article_panier')]
    public function addArticlePanier(int $id): Response
    {
        // j'appel notre méthode de notre class panier (add_article_panier)
        $this->panier->addArticlePanier($id);

        return $this->redirectToRoute('panier');
    }

    #[Route('/supprimer-panier', name: 'delete_panier')]
    public function deleteToutPanier(): Response
    {
        // j'appel la function deletePanier de notre classe panier qui suprime tout le panier

        $this->panier->deletePanier();

        // et je redirige vers la vue du panier
        return $this->redirectToRoute('panier');
    }

    #[Route('/supprimer-panier/{id}', name: 'delete_article_panier')]
    public function deleteArticlePanier(int $id): Response
    {
        // j'appel la function deletePanier de notre classe panier qui suprime tout les articles

        $this->panier->deleteArticlePanier($id);

        // et je redirige vers la vue du panier
        return $this->redirectToRoute('panier');
    }

    #[Route('/ajout-panier', name: 'add_5_panier')]
    public function add5joute5(): Response
    {
        // j'appel la function ajoute5 de notre classe panier qui ajoute 5 articles

        $this->panier->ajoute5();

        // et je redirige vers la vue du panier
        return $this->redirectToRoute('panier');
    }

    #[Route('/quantite-article/{id}', name: 'retire_une_quantite')]
    public function delete1Quantite(int $id): Response
    {
        // j'appel la function deleteUneQuantite de notre classe panier qui retire une quantité à un article

        $this->panier->deleteUneQuantite($id);

        // et je redirige vers la vue du panier
        return $this->redirectToRoute('panier');
    }
}