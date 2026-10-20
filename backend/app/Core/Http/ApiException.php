<?php

namespace App\Core\Http;

use Illuminate\Http\JsonResponse;
use RuntimeException;

/**
 * Erro de domínio devolvido à API no formato { message, code, errors }.
 */
class ApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $errorCode,
        public readonly int $status = 400,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public function render(): JsonResponse
    {
        return ApiError::response($this->getMessage(), $this->errorCode, $this->status, $this->errors);
    }
}
