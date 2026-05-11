<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ChangePasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/reset-password')]
class ResetPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('', name: 'app_forgot_password_request')]
    public function request(
        Request $request,
        MailerInterface $mailer,
    ): Response {
        $form = $this->createForm(ResetPasswordRequestFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = (string) $form->get('email')->getData();

            return $this->processSendingPasswordResetEmail(
                $email,
                $mailer
            );
        }

        return $this->render('reset_password/request.html.twig', [
            'requestForm' => $form->createView(),
        ]);
    }

    #[Route('/check-email', name: 'app_check_email')]
    public function checkEmail(): Response
    {
        // Génère un faux token si aucun token en session
        // afin d'éviter les attaques de type user enumeration
        if (null === $this->getTokenObjectFromSession()) {
            $fakeToken = $this->resetPasswordHelper->generateFakeResetToken();

            return $this->render('reset_password/check_email.html.twig', [
                'resetToken' => $fakeToken,
            ]);
        }

        return $this->render('reset_password/check_email.html.twig', [
            'resetToken' => $this->getTokenObjectFromSession(),
        ]);
    }

    #[Route('/reset/{token}', name: 'app_reset_password')]
    public function reset(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        ?string $token = null,
    ): Response {
        if (null !== $token) {
            // Stocke le token en session puis redirige
            // pour éviter qu’il reste dans l’URL
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('app_reset_password');
        }

        $token = $this->getTokenFromSession();

        if (null === $token) {
            throw $this->createNotFoundException('Aucun token de réinitialisation trouvé.');
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $exception) {
            $this->addFlash(
                'reset_password_error',
                sprintf(
                    '%s - %s',
                    ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE,
                    $exception->getReason()
                )
            );

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $form = $this->createForm(ChangePasswordFormType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->resetPasswordHelper->removeResetRequest($token);

            $plainPassword = (string) $form->get('plainPassword')->getData();

            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $plainPassword
            );

            $user->setPassword($hashedPassword);

            $this->entityManager->flush();

            $this->cleanSessionAfterReset();

            $this->addFlash(
                'success',
                '✔️ Votre mot de passe a été modifié avec succès. ✔️'
            );

            return $this->redirectToRoute('login');
        }

        return $this->render('reset_password/reset.html.twig', [
            'resetForm' => $form->createView(),
        ]);
    }

    private function processSendingPasswordResetEmail(
        string $emailFormData,
        MailerInterface $mailer,
    ): RedirectResponse {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy([
                'email' => $emailFormData,
            ]);

        // Ne jamais révéler si l'utilisateur existe ou non
        if (!$user instanceof User) {
            return $this->redirectToRoute('app_check_email');
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $exception) {
            $this->addFlash(
                'reset_password_error',
                sprintf(
                    '%s - %s',
                    ResetPasswordExceptionInterface::MESSAGE_PROBLEM_HANDLE,
                    $exception->getReason()
                )
            );

            return $this->redirectToRoute('app_forgot_password_request');
        }

        $email = (new TemplatedEmail())
            ->from(new Address('contact@cloud-quizz.fr', 'Cloud Quizz'))
            ->to((string) $user->getEmail())
            ->subject('Réinitialisation de votre mot de passe')
            ->htmlTemplate('reset_password/email.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ]);

        $mailer->send($email);

        // Stockage du token en session
        $this->setTokenObjectInSession($resetToken);

        return $this->redirectToRoute('app_check_email');
    }
}