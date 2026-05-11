<?php

namespace App\Controller;

use App\Classes\Panier;
use App\Entity\Commande;
use App\Entity\ProduitBoutique;
use App\Entity\User;
use App\Repository\ProduitBoutiqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        Stripe::setApiKey($stripeSK);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Ma commande',
                    ],
                    'unit_amount' => (int) round($panier->getTotalePanier() * 100),
                ],
                'quantity' => $panier->getNombreArticlePanier(),
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl(
                'success_url',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
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
        Panier $panierService,
        ProduitBoutiqueRepository $produitBoutiqueRepository,
        EntityManagerInterface $entityManager,
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $panier = $panierService->getPanier();

        if ([] === $panier) {
            $this->addFlash('error', 'Votre panier est vide.');

            return $this->redirectToRoute('panier');
        }

        $commande = new Commande();
        $commande->setUser($user);
        $commande->setReference((string) random_int(100000, 999999));
        $commande->setPrix($panierService->getTotalePanier());
        $commande->setCreatedAt(new \DateTimeImmutable());

        foreach ($panier as $productId => $quantity) {
            $produit = $produitBoutiqueRepository->find($productId);

            if (!$produit instanceof ProduitBoutique) {
                continue;
            }

            for ($i = 0; $i < $quantity; ++$i) {
                $commande->addProduit($produit);
            }
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