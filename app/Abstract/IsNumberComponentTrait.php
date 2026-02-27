<?php

namespace App\Abstract;

trait IsNumberComponentTrait
{
    /**
     * Checks if a character is part of a number (digit or decimal point).
     */
    protected function isNumberComponent(string $char): bool
    {
        return is_numeric($char) || $char === '.';
    }
}
