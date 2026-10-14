<?php

namespace App\Controller;

use App\Classes\Panier;
use App\Entity\Commande;
use App\Entity\LigneCommande;
use App\Entity\User;
use App\Repository\CommandeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaymentController extends AbstractController
{
    #[Route('/checkout', name: 'checkout')]
    public function checkout(
        string $stripeSK,
        Panier $panier,
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $detailPanier = $panier->getDetailPanier();

        if ([] === $detailPanier) {
            $this->addFlash('error', 'Votre panier est vide.');

            return $this->redirectToRoute('panier');
        }

        // Une ligne Stripe par produit, avec son vrai prix unitaire et sa quantité.
        // (Avant : le TOTAL du panier était envoyé comme prix unitaire, multiplié
        // par le nombre d'articles → 3 articles = payé 3 fois le total.)
        $lineItems = [];

        foreach ($detailPanier as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $item['article']->getTitre(),
                    ],
                    'unit_amount' => (int) round($item['article']->getPrix() * 100),
                ],
                'quantity' => $item['quantity'],
            ];
        }

        Stripe::setApiKey($stripeSK);

        $session = Session::create([
            // Lie la session de paiement à l'utilisateur : vérifié au retour sur /success
            'client_reference_id' => (string) $user->getId(),
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            // {CHECKOUT_SESSION_ID} est remplacé par Stripe par l'identifiant réel
            // de la session. Concaténé à la main (et non passé dans les paramètres
            // de generateUrl) pour que les accolades ne soient pas encodées.
            'success_url' => $this->generateUrl(
                'success_url',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->generateUrl(
                'cancel_url',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
        ]);

        return $this->redirect($session->url, Response::HTTP_SEE_OTHER);
    }

    #[Route('/success', name: 'success_url')]
    public function successUrl(
        Request $request,
        string $stripeSK,
        Panier $panierService,
        CommandeRepository $commandeRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        // 1. Sans identifiant de session, la page n'a pas été atteinte via Stripe
        $sessionId = (string) $request->query->get('session_id', '');

        if ('' === $sessionId) {
            return $this->redirectToRoute('panier');
        }

        // 2. Session déjà utilisée (rechargement de la page, URL rejouée) :
        //    la commande existe déjà, on n'en crée pas une seconde
        if (null !== $commandeRepository->findOneBy(['stripeSessionId' => $sessionId])) {
            return $this->render('payment/success.html.twig');
        }

        // 3. La preuve du paiement vient de Stripe, jamais du navigateur
        Stripe::setApiKey($stripeSK);

        try {
            $session = Session::retrieve($sessionId);
        } catch (ApiErrorException) {
            // Identifiant inventé ou appartenant à un autre compte Stripe
            throw $this->createNotFoundException('Session de paiement introuvable.');
        }

        // 4. Session payée, et par l'utilisateur connecté
        if ('paid' !== $session->payment_status
            || (string) $user->getId() !== $session->client_reference_id) {
            throw $this->createAccessDeniedException('Paiement non confirmé.');
        }

        $panier = $panierService->getPanier();

        if ([] === $panier) {
            $this->addFlash('error', 'Votre panier est vide.');

            return $this->redirectToRoute('panier');
        }

        // 5. Le montant payé correspond au panier (panier modifié entre-temps ?)
        if ((int) round($panierService->getTotalePanier() * 100) !== $session->amount_total) {
            $this->addFlash('error', 'Le contenu du panier a changé pendant le paiement.');

            return $this->redirectToRoute('panier');
        }

        $commande = new Commande();
        $commande->setUser($user);
        $commande->setReference(Commande::genererReference());
        $commande->setPrix($panierService->getTotalePanier());
        $commande->setCreatedAt(new \DateTimeImmutable());
        $commande->setStripeSessionId($sessionId);

        // Une ligne par produit, avec titre et prix copiés au moment de l'achat
        foreach ($panierService->getDetailPanier() as $item) {
            $commande->addLigne(LigneCommande::depuisProduit($item['article'], $item['quantity']));
        }

        $entityManager->persist($commande);
        $entityManager->flush();

        $panierService->deletePanier();

        return $this->render('payment/success.html.twig');
    }

    #[Route('/cancel', name: 'cancel_url')]
    public function cancelUrl(): Response
    {
        return $this->render('payment/cancel.html.twig');
    }
}