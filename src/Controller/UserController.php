<?php

namespace App\Controller;

use App\Entity\ActivationToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Service\ActivationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\MailerService;

final class UserController extends AbstractController
{

    private string $frontendLoginUrl;

    public function __construct()
    {
        $this->frontendLoginUrl = $_ENV['URL_LOGIN_FRONT'].'/login';
    }
    /**
     * Redirects to the API endpoint.
     */
    #[Route('/', name: 'redirect_to_api')]
    public function redirectToApi()
    {
        return $this->redirect('/api');
    }

    /**
     * Fetches a valid activation token for the specified user.
     * @param User $user
     * @param ActivationService $activationService
     * @return JsonResponse
     */
    #[Route('/api/users/{id}/token', methods: ['GET'])]
    public function getTokenForUser(User $user, ActivationService $activationService): JsonResponse
    {
        $token = $activationService->getValidTokenForUser($user);
    
        return $this->json([
            'user' => $user->getEmail(),
            'token' => $token->getHashedToken(),
            'expiresAt' => $token->getExpiredAt(),
        ]);
    }

    /**
     * Activates a user account based on the provided token.
     * Redirects to the frontend login page with activation status.
     *
     * @param string $token The activation token from the URL.
     * @param ActivationService $activationService The service to handle activation logic.
     * @return Response A redirect response to the frontend login page with query parameters indicating success or failure.
     */
    #[Route('/api/users/activate_account/{token}', name: 'user_activate', methods: ['GET'])]
    public function activate(string $token, ActivationService $activationService): Response
    {
        $success = $activationService->activateAccount($token);
        
        if ($success) {
            return $this->redirect("{$this->frontendLoginUrl}?activated=1",);
        }

        return $this->redirect("{$this->frontendLoginUrl}?activated=0&error=token_expired");
    }

    /**
     * Resends the activation email to the user with a new token.
     *
     * @param string $email The email address of the user requesting a new activation email.
     * @param ActivationService $activationService The service to handle activation logic.
     * @param MailerService $mailerService The service to send emails.
     * @param EntityManagerInterface $em The entity manager for database operations.
     * @return JsonResponse A JSON response indicating success or failure of the operation.
     */
    #[Route('/api/users/resend_activation_account/{email}', name: 'user_resend_activation', methods: ['GET', 'POST'])]
    public function resendActivation(string $email, ActivationService $activationService,
        MailerService $mailerService, EntityManagerInterface $em): Response {

        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user) {
            return $this->redirect("{$this->frontendLoginUrl}?activated=0&error=user_not_found");
        }

        if ($user->isActivated()) {
            return $this->redirect("{$this->frontendLoginUrl}?activated=1&existing=true");
        }

        // Générer un nouveau token
        $token = $activationService->generateToken($user);

        // Renvoyer l’e-mail
        $mailerService->sendConfirmationEmail(
            $user->getEmail(),
            $token,
            $user->getFirstName().' '.$user->getLastName(),
            true // Indique que c'est un renvoi
        );

        return new JsonResponse(['message' => 'Un nouvel e-mail de confirmation a été envoyé.']);
    }
}



