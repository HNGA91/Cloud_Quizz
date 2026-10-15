<?php

namespace App\Controller;

use App\Entity\Choix;
use App\Entity\JeuxQuizz;
use App\Entity\QuestionQuizz;
use App\Entity\ThemeQuizz;
use App\Entity\User;
use App\Form\EditProfileType;
use App\Repository\CommandeRepository;
use App\Repository\JeuxQuizzRepository;
use App\Repository\ProduitBoutiqueRepository;
use App\Repository\QuestionQuizzRepository;
use App\Repository\ThemeQuizzRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class PagesController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function home(ThemeQuizzRepository $themeQuizzRepository): Response
    {
        return $this->render('pages/home.html.twig', [
            'theme_quizzs' => $themeQuizzRepository->findAll(),
            // Un thème sans quizz jouable affiche un bouton « indisponible »
            'themes_jouables' => $themeQuizzRepository->findIdsJouables(),
        ]);
    }

    #[Route('/info', name: 'aproposdenous')]
    public function aproposdenous(): Response
    {
        return $this->render('pages/aproposdenous.html.twig');
    }

    #[Route('/boutique', name: 'boutique')]
    public function boutique(ProduitBoutiqueRepository $produitBoutiqueRepository): Response
    {
        return $this->render('pages/boutique.html.twig', [
            'produit_boutiques' => $produitBoutiqueRepository->findAll(),
        ]);
    }

    #[Route('/offres', name: 'offrepremium')]
    public function offrepremium(): Response
    {
        return $this->render('pages/offrepremium.html.twig');
    }

    #[Route('/theme/{id}', name: 'theme_quizz_page')]
    public function themeQuizzPage(ThemeQuizz $themeQuizz, JeuxQuizzRepository $jeuxQuizzRepository): Response
    {
        return $this->render('pages/jeux/themequizz.html.twig', [
            'themeQuizz' => $themeQuizz,
            'jeux_quizzs' => $jeuxQuizzRepository->findBy([
                'themeQuizz' => $themeQuizz,
            ]),
            'quizz_jouables' => $jeuxQuizzRepository->findIdsJouables($themeQuizz),
        ]);
    }

    #[Route('/moncompte', name: 'moncompte')]
    public function moncompte(CommandeRepository $commandeRepository): Response
    {
        $user = $this->getUser();

        return $this->render('pages/moncompte.html.twig', [
            'commandes' => $commandeRepository->findBy([
                'user' => $user,
            ]),
        ]);
    }

    #[Route('/moncompte/modifier', name: 'modifiermoncompte')]
    public function modifiermoncompte(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isGranted('ROLE_USER')) {
            throw $this->createAccessDeniedException('Impossible d’accéder à cette page !');
        }
        $user = $this->getUser();
        $form = $this->createForm(EditProfileType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('message', '✔️ Informations personnelles mises à jour ✔️');

            return $this->redirectToRoute('moncompte');
        }

        return $this->render('pages/modifiermoncompte.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/moncompte/modifier/motdepasse', name: 'modifiermotdepasse')]
    public function modifiermotdepasse(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {
            $entityManager->flush();

            /** @var User $user */
            $user = $this->getUser();

            // On vérifie si les 2 mots de passe sont identiques
            if ($request->request->get('editpassword') == $request->request->get('editpassword2')) {
                $user->setPassword($passwordHasher->hashPassword($user, $request->request->get('editpassword')));
                $entityManager->flush();
                $this->addFlash('message', '✔️ Mot de passe mis à jour ✔️');

                return $this->redirectToRoute('moncompte');
            }
            $this->addFlash('error', '❌ Les deux mots de passe ne sont pas identiques ❌');
        }

        return $this->render('pages/modifiermotdepasse.html.twig');
    }

    #[Route('/jeux/quizz/{id}', name: 'jeux_quizz_page')]
    public function jeuxQuizzPage(JeuxQuizz $jeuxQuizz, JeuxQuizzRepository $jeuxQuizzRepository): Response
    {
        // Avant : un paramètre ThemeQuizz était résolu avec le même {id} que le quizz,
        // donc le thème « n° id du quizz » (mauvais thème, ou 404 s'il n'existe pas).
        return $this->render('pages/jeux/jeuxquizz.html.twig', [
            'themeQuizz' => $jeuxQuizz->getThemeQuizz(),
            'jeuxQuizz' => $jeuxQuizz,
            'jouable' => in_array($jeuxQuizz->getId(), $jeuxQuizzRepository->findIdsJouables(), true),
        ]);
    }

    #[Route('/CGU', name: 'CGU')]
    public function mentionscgu(): Response
    {
        return $this->render('pages/cgu.html.twig');
    }

    #[Route('/politiquedeconfidentialite', name: 'conf')]
    public function conf(): Response
    {
        return $this->render('pages/conf.html.twig');
    }

    /*
     * API du jeu (appelée par public/javascript/quizz.js)
     *
     * La bonne réponse n'est JAMAIS envoyée avec la question : le navigateur envoie
     * le choix du joueur et c'est le serveur qui dit s'il est juste. Avant, la réponse
     * était lisible dans les outils de développement (onglet Réseau).
     */

    #[Route('/jeux/quizz/{id}/details', name: 'quizz_details', methods: ['GET'])]
    public function quizzDetails(JeuxQuizz $quizz, QuestionQuizzRepository $questionQuizzRepository): JsonResponse
    {
        $questions = $this->questionsJouables($quizz, $questionQuizzRepository);

        if ($questions === []) {
            return new JsonResponse([
                'error' => 'Aucune question trouvée',
            ], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'id' => $quizz->getId(),
            'name' => $quizz->getJeux(),
            'count' => count($questions),
            'question' => $this->questionEnTableau($questions[0]),
        ]);
    }

    #[Route('/jeux/quizz/{id}/questions/{questId}/next', name: 'quizz_next', methods: ['GET'])]
    public function quizzQuestionSuivante(JeuxQuizz $quizz, QuestionQuizzRepository $questionQuizzRepository, int $questId): JsonResponse
    {
        $questions = $this->questionsJouables($quizz, $questionQuizzRepository);

        foreach ($questions as $i => $question) {
            if ($question->getId() === $questId && isset($questions[$i + 1])) {
                return new JsonResponse($this->questionEnTableau($questions[$i + 1]));
            }
        }

        // Plus de question : fin du quizz
        return new JsonResponse(['fin' => true]);
    }

    #[Route('/jeux/quizz/{id}/questions/{questId}/reponse', name: 'quizz_reponse', methods: ['POST'])]
    public function quizzReponse(
        JeuxQuizz $quizz,
        int $questId,
        Request $request,
        QuestionQuizzRepository $questionQuizzRepository,
    ): JsonResponse {
        $question = $questionQuizzRepository->find($questId);

        // La question doit appartenir à CE quizz (sinon : identifiants inventés)
        if (!$question instanceof QuestionQuizz || $question->getJeuxQuizz() !== $quizz || !$question->estJouable()) {
            return new JsonResponse(['error' => 'Question introuvable'], Response::HTTP_NOT_FOUND);
        }

        $donnees = json_decode($request->getContent(), true);
        $choixId = is_array($donnees) && is_int($donnees['choix'] ?? null) ? $donnees['choix'] : null;

        $choisi = null;
        foreach ($question->getChoix() as $choix) {
            if ($choix->getId() === $choixId) {
                $choisi = $choix;
            }
        }

        if (null === $choisi) {
            return new JsonResponse(['error' => 'Choix invalide'], Response::HTTP_BAD_REQUEST);
        }

        $libelles = [];
        foreach ($question->getChoix() as $choix) {
            $libelles[$choix->getId()] = $choix->getLibelle();
        }

        return new JsonResponse([
            'correct' => $choisi->isEstCorrect(),
            'bonChoix' => $question->getBonChoix()?->getId(),
            // Les libellés des choix-images ne sont révélés qu'après la réponse
            'libelles' => $libelles,
        ]);
    }

    /*
     * Images du jeu servies sous une adresse neutre (/jeux/choix/150/image) :
     * l'adresse réelle (…/drapeaux/irlande.svg) donnerait la réponse à qui
     * survole ou ouvre l'image.
     */

    #[Route('/jeux/choix/{id}/image', name: 'quizz_choix_image', methods: ['GET'])]
    public function imageChoix(Choix $choix): Response
    {
        return $this->fichierImage($choix->getImage());
    }

    #[Route('/jeux/question/{id}/image', name: 'quizz_question_image', methods: ['GET'])]
    public function imageQuestion(QuestionQuizz $question): Response
    {
        return $this->fichierImage($question->getImage());
    }

    private function fichierImage(?string $chemin): Response
    {
        $dossierImages = realpath($this->getParameter('kernel.project_dir').'/public/images');
        $fichier = null === $chemin ? false : realpath($this->getParameter('kernel.project_dir').'/public/'.$chemin);

        // Le fichier doit exister ET se trouver dans public/images (pas de « ../ »)
        if (false === $fichier || false === $dossierImages || !str_starts_with($fichier, $dossierImages.DIRECTORY_SEPARATOR)) {
            throw $this->createNotFoundException('Image introuvable.');
        }

        $reponse = new BinaryFileResponse($fichier);
        $reponse->setPublic();
        $reponse->setMaxAge(86400); // mise en cache un jour par le navigateur

        return $reponse;
    }

    /**
     * Questions du quizz qui peuvent être jouées, dans l'ordre (les incomplètes sont ignorées).
     *
     * @return list<QuestionQuizz>
     */
    private function questionsJouables(JeuxQuizz $quizz, QuestionQuizzRepository $questionQuizzRepository): array
    {
        return array_values(array_filter(
            $questionQuizzRepository->findAvecChoix($quizz),
            static fn (QuestionQuizz $q): bool => $q->estJouable()
        ));
    }

    /**
     * Question envoyée au navigateur : texte, image, choix mélangés, sans la réponse.
     *
     * @return array<string, mixed>
     */
    private function questionEnTableau(QuestionQuizz $question): array
    {
        $choix = $question->getChoix()->toArray();
        shuffle($choix); // la bonne réponse n'est pas toujours au même endroit

        return [
            'id' => $question->getId(),
            'text' => $question->getQuestion(),
            'image' => null === $question->getImage() ? null
                : $this->generateUrl('quizz_question_image', ['id' => $question->getId()]),
            'choices' => array_map(fn (Choix $c): array => [
                'id' => $c->getId(),
                // Choix-image : le libellé (« Irlande ») donnerait la réponse, il est
                // envoyé seulement après la réponse du joueur
                'libelle' => null === $c->getImage() ? $c->getLibelle() : null,
                'image' => null === $c->getImage() ? null
                    : $this->generateUrl('quizz_choix_image', ['id' => $c->getId()]),
            ], $choix),
        ];
    }
}
