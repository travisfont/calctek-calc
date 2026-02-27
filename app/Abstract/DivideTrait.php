<?php

namespace App\Abstract;

use InvalidArgumentException;

trait DivideTrait
{
    /**
     * Perform division, ensuring no division by zero.
     *
     * @throws InvalidArgumentException
     */
    protected function divide(float $operand1, float $operand2): float
    {
        if ($operand2 === 0.0) { // cannot forget this
            throw new InvalidArgumentException('Division by zero is not allowed.');
        }

        return $operand1 / $operand2;
    }
}
