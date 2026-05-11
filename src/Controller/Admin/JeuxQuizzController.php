<?php

namespace App\Controller\Admin;

use App\Entity\JeuxQuizz;
use App\Form\JeuxQuizzType;
use App\Repository\JeuxQuizzRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/jeux/quizz')]
class JeuxQuizzController extends AbstractController
{
    #[Route('/liste-jeux-quizz', name: 'jeux_quizz_index', methods: ['GET'])]
    public function index(JeuxQuizzRepository $jeuxQuizzRepository): Response
    {
        return $this->render('jeux_quizz/index.html.twig', [
            'jeux_quizzs' => $jeuxQuizzRepository->findAll(),
        ]);
    }

    #[Route('/ajouter-jeux-quizz', name: 'jeux_quizz_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $jeuxQuizz = new JeuxQuizz();
        $form = $this->createForm(JeuxQuizzType::class, $jeuxQuizz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($jeuxQuizz);
            $entityManager->flush();

            return $this->redirectToRoute('jeux_quizz_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('jeux_quizz/new.html.twig', [
            'jeux_quizz' => $jeuxQuizz,
            'form' => $form,
        ]);
    }

    #[Route('/jeux-quizz/{id}', name: 'jeux_quizz_show', methods: ['GET'])]
    public function show(JeuxQuizz $jeuxQuizz): Response
    {
        return $this->render('jeux_quizz/show.html.twig', [
            'jeux_quizz' => $jeuxQuizz,
        ]);
    }

    #[Route('/jeux-quizz/{id}/modifier', name: 'jeux_quizz_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, JeuxQuizz $jeuxQuizz, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(JeuxQuizzType::class, $jeuxQuizz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('jeux_quizz_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('jeux_quizz/edit.html.twig', [
            'jeux_quizz' => $jeuxQuizz,
            'form' => $form,
        ]);
    }

    #[Route('/jeux-quizz/{id}', name: 'jeux_quizz_delete', methods: ['POST'])]
    public function delete(Request $request, JeuxQuizz $jeuxQuizz, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$jeuxQuizz->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($jeuxQuizz);
            $entityManager->flush();
        }

        return $this->redirectToRoute('jeux_quizz_index', [], Response::HTTP_SEE_OTHER);
    }
}
