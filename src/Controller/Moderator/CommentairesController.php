<?php

namespace App\Controller\Moderator;

use App\Entity\Commentaires;
use App\Form\Commentaires1Type;
use App\Repository\CommentairesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/moderator/commentaires')]
class CommentairesController extends AbstractController
{
    #[Route('/liste-commentaire', name: 'commentaires_index', methods: ['GET'])]
    public function index(CommentairesRepository $commentairesRepository): Response
    {
        return $this->render('commentaires/index.html.twig', [
            'commentaires' => $commentairesRepository->findAll(),
        ]);
    }

    #[Route('/ajouter-commentaire', name: 'commentaires_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $commentaire = new Commentaires();
        $form = $this->createForm(Commentaires1Type::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($commentaire);
            $entityManager->flush();

            return $this->redirectToRoute('commentaires_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('commentaires/new.html.twig', [
            'commentaire' => $commentaire,
            'form' => $form,
        ]);
    }

    #[Route('/commentaire/{id}', name: 'commentaires_show', methods: ['GET'])]
    public function show(Commentaires $commentaire): Response
    {
        return $this->render('commentaires/show.html.twig', [
            'commentaire' => $commentaire,
        ]);
    }

    #[Route('/commentaire/{id}/modifier', name: 'commentaires_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Commentaires $commentaire, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(Commentaires1Type::class, $commentaire);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('commentaires_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('commentaires/edit.html.twig', [
            'commentaire' => $commentaire,
            'form' => $form,
        ]);
    }

    #[Route('/commentaire/{id}', name: 'commentaires_delete', methods: ['POST'])]
    public function delete(Request $request, Commentaires $commentaire, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$commentaire->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($commentaire);
            $entityManager->flush();
        }

        return $this->redirectToRoute('commentaires_index', [], Response::HTTP_SEE_OTHER);
    }
}
