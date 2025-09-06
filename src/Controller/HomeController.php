<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use App\Service\ActivationService;
use Symfony\Component\HttpFoundation\JsonResponse;

final class HomeController extends AbstractController
{
    /**
     * Redirects to the API endpoint.
     */
    #[Route('/', name: 'redirect_to_api')]
    public function redirectToApi()
    {
        return $this->redirect('/api');
    }

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

    #[Route('/api/users/activate_account/{token}', name: 'user_activate', methods: ['GET'])]
    public function activate(string $token, ActivationService $activationService): Response
    {
        $success = $activationService->activateAccount($token);

        if ($success) {
            return new JsonResponse(['message' => 'Compte activé avec succès !']);
        }

        return new JsonResponse(['message' => 'Token invalide ou expiré.'], 400);
    }

}


