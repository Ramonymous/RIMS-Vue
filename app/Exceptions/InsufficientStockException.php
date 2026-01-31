<?php

namespace App\Exceptions;

class InsufficientStockException extends BusinessException
{
    public function __construct(string $partNumber, int $available, int $required)
    {
        parent::__construct(
            message: "Insufficient stock for part {$partNumber}. Available: {$available}, Required: {$required}",
            statusCode: 422,
            context: [
                'part_number' => $partNumber,
                'available' => $available,
                'required' => $required,
            ]
        );
    }
}
