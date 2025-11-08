<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Interfaces\CurrentUserTokenInterface;
use App\Entity\User;
use App\Interfaces\ActiveActivationTokenInterface;
use App\Interfaces\UserProviderInterface;
use Symfony\Component\HttpFoundation\JsonResponse;


final class UtilityController extends AbstractController
{

    /**
     * Constructor.
     * @param EntityManagerInterface $entityManager
     * @param UserProviderInterface $userProvider
     * @param ActiveActivationTokenInterface $activeActivationToken
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserProviderInterface $userProvider,
        private ActiveActivationTokenInterface $activeActivationToken
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
     * Retrieves the activation token for a specific user.
     * @param int $id The ID of the user.
     * @return JsonResponse
     */
    #[Route('/api/users/{id}/token', methods: ['GET'])]
    public function getTokenForUser(int $id): JsonResponse
    {
        $user = $this->userProvider->getUserById($id);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        $token = $this->activeActivationToken->currentActiveTokenForUser($user);

        if (!$token) {
            return new JsonResponse(null, 204);
        }   

        return new JsonResponse(['token' => $token,], 200);
    }
}