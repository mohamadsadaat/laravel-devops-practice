<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(string $ageLabel, int $requested, int $available)
    {
        $message = "Insufficient stock for age {$ageLabel}. Requested: {$requested}, Available: {$available}";
        parent::__construct($message, 422);
    }

    public function render()
    {
        return response()->json([
            'error' => 'insufficient_stock',
            'message' => $this->getMessage(),
            'code' => 422
        ], 422);
    }
}
