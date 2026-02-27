<?php

namespace App\Abstract;

trait IsOperatorTrait
{
    /**
     * Checks if a character is a valid mathematical operator.
     */
    protected function isOperator(string $char): bool
    {
        return in_array($char, ['+', '-', '*', '/', '^'], true);
    }
}
