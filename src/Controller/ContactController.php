<?php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'contact')]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // On crée le mail
            $email = (new TemplatedEmail())
                ->from($form->get('email')->getData())
                ->to('contact@cloud-quizz.fr')
                ->subject('Cloud Quizz contact')
                ->htmlTemplate('emails/contactindex.html.twig')
                ->context([
                    'nom' => $form->get('nom')->getData(),
                    'prenom' => $form->get('prenom')->getData(),
                    'mail' => $form->get('email')->getData(),
                    'titre' => $form->get('titre')->getData(),
                    'message' => $form->get('message')->getData(),
                ]);
            // On envoie le mail
            $mailer->send($email);

            // On confirme et on redirige
            $this->addFlash('success', '✔️ Votre email a bien été envoyé. ✔️');

            return $this->redirectToRoute('contact');
        }

        return $this->render('pages/contact.html.twig', [
            'contactForm' => $form->createView(),
        ]);
    }
}
