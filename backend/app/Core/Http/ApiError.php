<?php

namespace App\Core\Http;

use Illuminate\Http\JsonResponse;

class ApiError
{
    public static function response(string $message, string $code, int $status, array $errors = []): JsonResponse
    {
        $body = ['message' => $message, 'code' => $code];

        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return new JsonResponse($body, $status);
    }
}
