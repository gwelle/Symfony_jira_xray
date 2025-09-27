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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class UserController extends AbstractController
{
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
            return $this->redirectToFrontend(['activated' => 1]);
        case 'already_activated':
            return $this->redirectToFrontend(['activated' => 1, 'info' => 'already_activated']);
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

            return $this->redirectToFrontend(['activated' => 0, 'error' => 'token_expired']);
        case 'blocked':
            return $this->redirectToFrontend(['activated' => 0, 'error' => 'max_resend_reached']);
        case 'invalid':
        default:
            return $this->redirectToFrontend(['activated' => 0, 'error' => 'invalid_token']);
        }
    }

    /**
     * Resends the activation email to the user with a new token.
     * @param Request $request The HTTP request containing the email in the JSON body.
     * @param ActivationService $activationService The service to handle activation logic.
     * @param MailerService $mailerService The service to send emails.
     * @param EntityManagerInterface $em The entity manager for database operations.
     * @param RateLimiterFactory $tokenInvalidLimiter The rate limiter to prevent abuse.
     * @return JsonResponse A JSON response indicating success or failure of the operation.
     */
    #[Route('/api/users/resend_activation_account', name: 'user_resend_activation', methods: ['POST'])]
    public function resendActivation(Request $request, ActivationService $activationService,
        MailerService $mailerService, EntityManagerInterface $em,
        #[Autowire(service: 'limiter.token_invalid_limiter')]
        RateLimiterFactory $tokenInvalidLimiter): Response {

        // Extraire l’email de la requête JSON
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        // Vérifier que l’email est fourni et valide 
        // et que l’utilisateur existe et n’est pas encore activé
        $user = $em->getRepository(User::class)->findOneBy([
            'email' => $email,
            'isActivated' => false
        ]);

        if (!$user) {
            return $this->json([
            'status' => 'handled',
            'info' => 'check_resend_email'
            ],404);
        }

        // Générer un nouveau token
        $token = $activationService->generateToken($user);

        // Appliquer le rate limiter
        $limiter = $tokenInvalidLimiter->create($email);

        // Vérifier si la limite est atteinte
        if (!$limiter->consume(1)->isAccepted()) {
            return $this->json([
                'status' => 'error',
                'error' => 'max_resend_reached'
            ],429);
        }

        // Renvoyer l’e-mail
        $mailerService->sendConfirmationEmail(
            $user->getEmail(),
            $token,
            $user->getFirstName().' '.$user->getLastName(),
            true // Indique que c'est un renvoi
        );
        return $this->json([
            'status' => 'resend',
            'info' => 'check_resend_email'
        ],200);
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
        ], 200);
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
        ], 200);
    }

    /**
     * Redirects to the frontend login page with optional query parameters.
     * @param array $params Optional query parameters to append to the URL.
     * @return RedirectResponse A redirect response to the frontend login page.
     */
    private function redirectToFrontend(array $params = []): RedirectResponse
    {
        $frontendLoginUrl = $_ENV['URL_LOGIN_FRONT'].'/login';
        // Append any additional query parameters encoded and securely
        return $this->redirect($frontendLoginUrl . '?' . http_build_query($params));
    }
}

