<?php 

namespace App\Response;

use Symfony\Component\HttpFoundation\JsonResponse;

class ResendMailResponse extends JsonResponse
{
    public function __construct(mixed $data, int $statusCode = 200, ?string $message = null)
    {
        // Si un message est fourni, on l’ajoute dans le tableau de données
        if ($message !== null) {
            $data['message'] = $message;
        }

        parent::__construct($data, $statusCode);
    }
}