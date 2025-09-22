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
use Symfony\Component\Validator\Constraints\Json;

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
     * Activates a user account based on the provided token.
     * Redirects to the frontend login page with activation status.
     *
     * @param string $token The activation token from the URL.
     * @param ActivationService $activationService The service to handle activation logic.
     * @param MailerService $mailerService The service to send emails.
     * @param EntityManagerInterface $em The entity manager for database operations.
     * @return Response A redirect response to the frontend login page with query parameters indicating success or failure.
     */
    #[Route('/api/users/activate_account/{token}', name: 'user_activate', methods: ['GET'])]
    public function activate(string $token, ActivationService $activationService,MailerService $mailerService,
    EntityManagerInterface $em): Response
    {
    $plainToken = trim($token);
    $results = $activationService->activateAccount($plainToken);

    switch ($results['status']) {
        case 'success':
            return $this->redirect("{$this->frontendLoginUrl}?activated=1");
        case 'already_activated':
            return $this->redirect("{$this->frontendLoginUrl}?activated=1&info=already_activated");
        case 'expired':
            $tokenExpired = $results['token']; // objet ActivationToken
            $user = $tokenExpired->getAccount();

            // Génération nouveau token
            $newToken = $activationService->generateToken($user);

            // Envoi email de confirmation
            $mailerService->sendConfirmationEmail(
                $user->getEmail(),
                $newToken,
                $user->getFirstName().' '.$user->getLastName(),
                true
            );
            
            $user->setResendCount($user->getResendCount() + 1);
            $user->setIsResend(true);
            $em->flush();

            return $this->redirect("{$this->frontendLoginUrl}?activated=0&error=token_expired&resend={$user->getResendCount()}");
        case 'blocked':
            return $this->redirect("{$this->frontendLoginUrl}?activated=0&error=max_resend_reached");
        case 'invalid':
        default:
            return $this->redirect("{$this->frontendLoginUrl}?activated=0&error=invalid_token");
        }
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

        /*if ($user->isActivated()) {
            return $this->redirect("{$this->frontendLoginUrl}?activated=1&info=already_activated");
        }*/

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

    /**
     * Refreshes expired activation tokens for users who haven't activated their accounts.
     * @param ActivationService $activationService The service to handle activation logic.
     * @return JsonResponse A JSON response indicating the result of the token refresh operation.
     */
    #[Route('/api/users/refresh_tokens', name: 'refresh_tokens', methods: ['POST'])]
    public function refreshExpiredTokens(ActivationService $activationService): JsonResponse
    {
        // Appelle la méthode pour rafraîchir les tokens expirés
        $results = $activationService->refreshExpiredTokens();
        

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Tokens refreshed',
            'data' => $results,
        ]);
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
}
