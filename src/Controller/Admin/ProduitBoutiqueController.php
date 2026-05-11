<?php

namespace App\Controller\Admin;

use App\Entity\ProduitBoutique;
use App\Form\ProduitBoutiqueType;
use App\Repository\ProduitBoutiqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/produit/boutique')]
class ProduitBoutiqueController extends AbstractController
{
    #[Route('/liste-produit', name: 'produit_boutique_index', methods: ['GET'])]
    public function index(ProduitBoutiqueRepository $produitBoutiqueRepository): Response
    {
        return $this->render('produit_boutique/index.html.twig', [
            'produit_boutiques' => $produitBoutiqueRepository->findAll(),
        ]);
    }

    #[Route('/ajouter-produit', name: 'produit_boutique_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // création de l'object article
        $produitBoutique = new ProduitBoutique();

        // création de l'objet formulaire sur la base de notre ArticleType qui ce trouve dans le dossier form
        // ensuite en le lie a notre objet article , grâce a la fonction creatForm
        $form = $this->createForm(ProduitBoutiqueType::class, $produitBoutique);

        // on met notre formulaire a l'ecoute d'une reponse grâce a l'objet Request
        $form->handleRequest($request);

        // en suite on test s'il y a eut validation du formulaire sur la vue
        if ($form->isSubmitted() && $form->isValid()) {
            // j'envoie à la bdd l'object $produitBoutique
            $entityManager->persist($produitBoutique);
            $entityManager->flush();

            // une fois fini je redirige vers ma page qui affuche la liste des article mise a jour
            return $this->redirectToRoute('produit_boutique_index');
        }

        // j'envoi la vue avec le formulaire viérge
        return $this->render('produit_boutique/new.html.twig', [
            'produit_boutique' => $produitBoutique,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/produit/{id}', name: 'produit_boutique_show', methods: ['GET'])]
    public function show(ProduitBoutique $produitBoutique): Response
    {
        return $this->render('produit_boutique/show.html.twig', [
            'produit_boutique' => $produitBoutique,
        ]);
    }

    #[Route('/produit/{id}/modifier', name: 'produit_boutique_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ProduitBoutique $produitBoutique, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProduitBoutiqueType::class, $produitBoutique);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('produit_boutique_index');
        }

        return $this->render('produit_boutique/edit.html.twig', [
            'produit_boutique' => $produitBoutique,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/produit/{id}', name: 'produit_boutique_delete', methods: ['POST'])]
    public function delete(Request $request, ProduitBoutique $produitBoutique, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$produitBoutique->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($produitBoutique);
            $entityManager->flush();
        }

        return $this->redirectToRoute('produit_boutique_index');
    }
}
