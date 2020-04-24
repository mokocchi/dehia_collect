<?php

namespace App\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiProblemResponseFactory
{
    public function createResponse(ApiProblem $apiProblem)
    {
        return new JsonResponse(
            $apiProblem->toArray(),
            $apiProblem->getStatusCode()
        );
    }
}
