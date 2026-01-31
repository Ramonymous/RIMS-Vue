<?php

namespace App\Exceptions;

use RuntimeException;

class BusinessException extends RuntimeException
{
    public function __construct(
        string $message = '',
        public readonly int $statusCode = 422,
        public readonly array $context = []
    ) {
        parent::__construct($message);
    }

    public function render()
    {
        return response()->json([
            'message' => $this->message,
            'errors' => $this->context,
        ], $this->statusCode);
    }
}
