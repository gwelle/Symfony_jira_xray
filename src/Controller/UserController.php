<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Service\ActivationService;
use App\Service\MailerService;
use App\Message\SendConfirmationEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

final class UserController extends AbstractController
{

    /**
     * Summary of __construct
     * @param \App\Service\ActivationService $activationService
     * @param \App\Service\MailerService $mailerService
     * @param \Doctrine\ORM\EntityManagerInterface $em
     * @param \Symfony\Component\Messenger\MessageBusInterface $bus
     */
    public function __construct(
        private ActivationService $activationService,
        private MailerService $mailerService,
        private EntityManagerInterface $em,
        private MessageBusInterface $bus
    ) {}

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
     * @return Response A redirect response to the frontend login page with query parameters indicating success or failure.
     */
    #[Route('/api/users/activate_account/{token}', name: 'user_activate', methods: ['GET'])]
    public function activate(string $token): Response
    {
    $results = $this->activationService->activateAccount($token);

    switch ($results['status']) {
        case 'success':
            return $this->json(['success' => 'Compte activé'], 200);
        case 'already_activated':
            return $this->json(['info' => 'already_activated'], 200);
        case 'expired':
            $tokenExpired = $results['token']; // objet ActivationToken
            $user = $tokenExpired->getAccount();

            // Génération nouveau token
            $newToken = $this->activationService->generateToken($user);

            // Envoi email de confirmation
            $this->bus->dispatch(new SendConfirmationEmail(
                $user->getEmail(),
                $newToken,
                $user->getFirstName().' '.$user->getLastName(),
                true
            ));
            return $this->json(['error' => 'token_expired'], 400);
        case 'blocked':
            return $this->json(['error' => 'max_resend_reached'], 429);
        case 'invalid':
        default:
            return $this->json(['error' => 'invalid_token'], 400);
        }
    }

    /**
     * Resends the activation email to the user with a new token.
     * @param Request $request The HTTP request containing the email in the JSON body.
     * @param RateLimiterFactory $tokenInvalidLimiter The rate limiter to prevent abuse.
     * @return JsonResponse A JSON response indicating success or failure of the operation.
     */
    #[Route('/api/users/resend_activation_account', name: 'user_resend_activation', methods: ['POST'])]
    public function resendActivation(Request $request,
        #[Autowire(service: 'limiter.token_invalid_limiter')]
        RateLimiterFactory $tokenInvalidLimiter): Response {

        // Extraire l’email de la requête JSON
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        // Vérifier que l’email est fourni et valide 
        // et que l’utilisateur existe et n’est pas encore activé
        $user = $this->em->getRepository(User::class)->findOneBy([
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
        $token = $this->activationService->generateToken($user);

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
        $this->bus->dispatch(new SendConfirmationEmail(
                $user->getEmail(),
                $token,
                $user->getFirstName().' '.$user->getLastName(),
                true
        ));

        return $this->json([
            'status' => 'resend',
            'info' => 'check_resend_email'
        ],200);
    }

    /**
     * Refreshes expired activation tokens for users who haven't activated their accounts.
     * @return JsonResponse A JSON response indicating the result of the token refresh operation.
     */
    #[Route('/api/users/refresh_tokens', name: 'refresh_tokens', methods: ['POST'])]
    public function refreshExpiredTokens(): JsonResponse
    {
        // Appelle la méthode pour rafraîchir les tokens expirés
        $results = $this->activationService->refreshExpiredTokens();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Tokens refreshed'
        ], 200);
    }

    /**
     * Retrieves the activation token for a specific user.
     * @param User $user
     * @return JsonResponse
     */
    #[Route('/api/users/{id}/token', methods: ['GET'])]
    public function getTokenForUser(User $user): JsonResponse
    {
        $token = $this->activationService->getValidTokenForUser($user);
        if( !$token ) {
            return $this->json(['error' => 'User not found'], 404);
        }
        return $this->json(['token' => $token], 200);
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
