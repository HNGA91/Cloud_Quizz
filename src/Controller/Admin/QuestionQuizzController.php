<?php

namespace App\Controller\Admin;

use App\Entity\QuestionQuizz;
use App\Form\QuestionQuizzType;
use App\Repository\QuestionQuizzRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/question/quizz')]
class QuestionQuizzController extends AbstractController
{
    #[Route('/liste-question-quizz', name: 'question_quizz_index', methods: ['GET'])]
    public function index(QuestionQuizzRepository $questionQuizzRepository): Response
    {
        return $this->render('question_quizz/index.html.twig', [
            'question_quizzs' => $questionQuizzRepository->findAll(),
        ]);
    }

    #[Route('/ajouter-question-quizz', name: 'question_quizz_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $questionQuizz = new QuestionQuizz();
        $form = $this->createForm(QuestionQuizzType::class, $questionQuizz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($questionQuizz);
            $entityManager->flush();

            return $this->redirectToRoute('question_quizz_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('question_quizz/new.html.twig', [
            'question_quizz' => $questionQuizz,
            'form' => $form,
        ]);
    }

    #[Route('/question-quizz/{id}', name: 'question_quizz_show', methods: ['GET'])]
    public function show(QuestionQuizz $questionQuizz): Response
    {
        return $this->render('question_quizz/show.html.twig', [
            'question_quizz' => $questionQuizz,
        ]);
    }

    #[Route('/question-quizz/{id}/modifier', name: 'question_quizz_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, QuestionQuizz $questionQuizz, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuestionQuizzType::class, $questionQuizz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('question_quizz_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('question_quizz/edit.html.twig', [
            'question_quizz' => $questionQuizz,
            'form' => $form,
        ]);
    }

    #[Route('/question-quizz/{id}', name: 'question_quizz_delete', methods: ['POST'])]
    public function delete(Request $request, QuestionQuizz $questionQuizz, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$questionQuizz->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($questionQuizz);
            $entityManager->flush();
        }

        return $this->redirectToRoute('question_quizz_index', [], Response::HTTP_SEE_OTHER);
    }
}
