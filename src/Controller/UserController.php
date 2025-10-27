<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Service\ActivationService;
use App\Message\SendConfirmationEmail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Response\UserActivatedResponse;
use App\Response\ResendMailResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\MessageBusInterface;

final class UserController extends AbstractController
{

    /**
     * Summary of __construct
     * @param ActivationService $activationService
     * @param EntityManagerInterface $entityManager
     * @param MessageBusInterface $bus
     */
    public function __construct(
        private ActivationService $activationService,
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus
    ) {}

    /**
     * Redirects to the API endpoint.
     */
    #[Route('/', name: 'redirect_to_api')]
    public function redirectToApi(): RedirectResponse
    {
        return $this->redirect('/api');
    }

    /**
     * Activates a user account based on the provided token.
     * Redirects to the frontend login page with activation status.
     * @param string $token The activation token from the URL.
     * @return UserActivatedResponse A redirect response to the frontend login page with query parameters indicating success or failure.
     */
    #[Route('/api/users/activate_account/{token}', name: 'user_activate', methods: ['GET'])]
    public function activate(string $token): UserActivatedResponse
    {
        $results = $this->activationService->activateAccount($token);
        $status = $results->status ?? null;
        try {
            switch ($status) {
                case 'success':
                    return new UserActivatedResponse(
                        ["status" => "Account activated"], 200);
                    /*return new RedirectFrontendResponse(['activated' => 1]);*/
                case 'already_activated':
                    return new UserActivatedResponse(
                        ["status" => "Account already activated"], 200);
                case 'expired':
                    $tokenExpired = $results->data ?? null; // objet ActivationToken
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
                    return new UserActivatedResponse(
                        ["error" => "Token expired"], 410
                    );
                case 'blocked':
                    return new UserActivatedResponse(
                        ["error" => "Max resend reached"], 429
                    );
                case 'invalid':
                default:
                    return new UserActivatedResponse(
                        ["error" => "Invalid Token"], 400
                    );
                }
            }
            
        catch (\Throwable $e) {
            // Retourne toujours un JSON pour Jest
            return new UserActivatedResponse(
                ["error" => "internal error", "message" => $e->getMessage()], 500);  
        }
    }

    /**
     * Resends the activation email to the user with a new token.
     * @param Request $request The HTTP request containing the email in the JSON body.
     * @param RateLimiterFactory $tokenInvalidLimiter The rate limiter to prevent abuse.
     * @return ResendMailResponse A response indicating the result of the resend operation.
     */
    #[Route('/api/users/resend_activation_account', name: 'user_resend_activation', methods: ['POST'])]
    public function resendActivation(Request $request,
        #[Autowire(service: 'limiter.token_invalid_limiter')]
        RateLimiterFactory $tokenInvalidLimiter): ResendMailResponse {

        // Extraire l’email de la requête JSON
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;

        // Vérifier que l’email est fourni et valide 
        // et que l’utilisateur existe et n’est pas encore activé
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $email,
            'isActivated' => false
        ]);

        if (!$user) {
            return new ResendMailResponse(
                ['status' => 'handled', 'info' => 'Checking resend email'], 404);
        }

        // Générer un nouveau token
        $token = $this->activationService->generateToken($user);

        // Appliquer le rate limiter
        $limiter = $tokenInvalidLimiter->create($email);

        // Vérifier si la limite est atteinte
        if (!$limiter->consume(1)->isAccepted()) {
            return new ResendMailResponse(
                ['status' => 'error', 'error' => 'Max resend reached'], 429);
        }

        // Renvoyer l’e-mail
        $this->bus->dispatch(new SendConfirmationEmail(
                $user->getEmail(),
                $token,
                $user->getFirstName().' '.$user->getLastName(),
                true
        ));

        return new ResendMailResponse(
            ['status' => 'resend', 'info' => 'Checking resend email'], 200);
    }

    /**
     * Refreshes expired activation tokens for users who haven't activated their accounts.
     * @return JsonResponse A JSON response indicating the result of the token refresh operation.
     */
    #[Route('/api/users/refresh_tokens', name: 'refresh_tokens', methods: ['POST'])]
    public function refreshExpiredTokens(): JsonResponse
    {
        // Appelle la méthode pour rafraîchir les tokens expirés
        $this->activationService->refreshExpiredTokens();

        return new JsonResponse([
            'status' => 'success',
            'message' => 'Tokens regenerated after many token expirations',
        ], 200);
    }

    /**
     * Retrieves the activation token for a specific user.
     * @param int $id The ID of the user.
     * @return JsonResponse
     */
    #[Route('/api/users/{id}/token', methods: ['GET'])]
    public function getTokenForUser(int $id): JsonResponse
    {
        $user = $this->entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $token = $this->activationService->getValidTokenForUser($user);

        if (!$token) {
            return new JsonResponse(null, 204);
        }   

        return new JsonResponse(['token' => $token,], 200);
    }
}
