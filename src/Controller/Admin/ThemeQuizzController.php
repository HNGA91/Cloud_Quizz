<?php

namespace App\Controller\Admin;

use App\Entity\ThemeQuizz;
use App\Form\ThemeQuizzType;
use App\Repository\ThemeQuizzRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/theme/quizz')]
class ThemeQuizzController extends AbstractController
{
    #[Route('/liste-theme-quizz', name: 'theme_quizz_index', methods: ['GET'])]
    public function index(ThemeQuizzRepository $themeQuizzRepository): Response
    {
        return $this->render('theme_quizz/index.html.twig', [
            'theme_quizzs' => $themeQuizzRepository->findAll(),
        ]);
    }

    #[Route('/ajouter-theme-quizz', name: 'theme_quizz_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $themeQuizz = new ThemeQuizz();
        $form = $this->createForm(ThemeQuizzType::class, $themeQuizz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($themeQuizz);
            $entityManager->flush();

            return $this->redirectToRoute('theme_quizz_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('theme_quizz/new.html.twig', [
            'theme_quizz' => $themeQuizz,
            'form' => $form,
        ]);
    }

    #[Route('/theme-quizz/{id}', name: 'theme_quizz_show', methods: ['GET'])]
    public function show(ThemeQuizz $themeQuizz): Response
    {
        return $this->render('theme_quizz/show.html.twig', [
            'theme_quizz' => $themeQuizz,
        ]);
    }

    #[Route('/theme-quizz/{id}/modifier', name: 'theme_quizz_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ThemeQuizz $themeQuizz, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ThemeQuizzType::class, $themeQuizz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('theme_quizz_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('theme_quizz/edit.html.twig', [
            'theme_quizz' => $themeQuizz,
            'form' => $form,
        ]);
    }

    #[Route('/theme-quizz/{id}', name: 'theme_quizz_delete', methods: ['POST'])]
    public function delete(Request $request, ThemeQuizz $themeQuizz, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$themeQuizz->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($themeQuizz);
            $entityManager->flush();
        }

        return $this->redirectToRoute('theme_quizz_index', [], Response::HTTP_SEE_OTHER);
    }
}
