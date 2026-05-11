<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Commentaires;
use App\Entity\JeuxQuizz;
use App\Form\CommentairesType;
use App\Repository\CommentairesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ForumController extends AbstractController
{
    private Security $security;

    public function __construct(Security $security)
    {
        // Avoid calling getUser() in the constructor: auth may not
        // be complete yet. Instead, store the entire Security object.
        $this->security = $security;
    }

    #[Route('/forum/{id}', name: 'jeux_forum')]
    public function indexForumDrapeau(JeuxQuizz $jeuxQuizz, CommentairesRepository $commmentairesRepository, Request $request, EntityManagerInterface $entityManager): Response
    {
        // On créé le commentaire "vierge"
        $commentaire = new Commentaires();

        /** @var User|null $user */
        $user = $this->security->getUser();

        if ($user === null) {
            throw $this->createAccessDeniedException();
        }

        // On génère le formulaire
        $form = $this->createForm(CommentairesType::class, $commentaire);
        $user = $this->security->getUser();

        $form->handleRequest($request);

        // Traitement du formulaire
        if ($form->isSubmitted() && $form->isValid()) {
            $commentaire->setCreatedAt(new \DateTimeImmutable());
            $commentaire->setUser($user);
            $jeuxQuizz->addCommentaire($commentaire);

            // On récupère le contenu du champ parentid
            $parentid = $form->get('parent')->getData();

            // On va chercher le commentaire correspondant
            if (null != $parentid) {
                $parent = $entityManager->getRepository(Commentaires::class)->find($parentid);
            }

            // On définit le parent
            $commentaire->setParent($parent ?? null);

            $entityManager->persist($commentaire);
            $entityManager->flush();

            $this->addFlash('message', '✔️ Votre commentaire a bien été envoyé ! ✔️');

            return $this->redirectToRoute('jeux_forum', ['id' => $jeuxQuizz->getId()]);
        }

        return $this->render('forum/jeuxforum.html.twig', [
            'jeuxQuizz' => $jeuxQuizz,
            'commentaires' => $commmentairesRepository->findAll(),
            'form' => $form->createView(),
        ]);
    }
}
