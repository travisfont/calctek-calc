<?php

namespace App\Services;

use InvalidArgumentException;

class CalculationService
{
    /**
     * Parses and evaluates a mathematical expression string.
     * Supports +, -, *, / and respects order of operations.
     *
     * @throws InvalidArgumentException
     */
    public function calculate(string $expression): float
    {
        if (trim($expression) === '') {
            throw new InvalidArgumentException('Expression cannot be empty.');
        }

        // this gets a array of numbers and operators
        $elements = $this->extractNumbersAndOperators($expression); // parsing step 1
        // this reorders the elements based on operator priority
        $orderedElements = $this->reorderElementsByPriority($elements); // parsing step 2

        // this evaluates the ordered elements
        return $this->evaluateOrderedElements($orderedElements); // evaluatio step 3
    }

    /**
     * Separates the mathematical expression into individual numbers and operators.
     * Takes care of handling negative numbers correctly.
     *
     * @return array<int, string>
     */
    private function extractNumbersAndOperators(string $expression): array
    {
        $expression = str_replace(' ', '', $expression);

        $elements = [];
        $length = strlen($expression);
        $currentIndex = 0;

        while ($currentIndex < $length) {
            $char = $expression[$currentIndex];

            if ($this->isNumberComponent($char)) {
                $elements[] = $this->extractNumber($expression, $currentIndex);

                continue;
            }

            if ($this->isOperator($char)) {
                $previousElement = empty($elements) ? null : end($elements);
                $elements[] = $this->extractOperatorOrNegativeNumber($expression, $previousElement, $currentIndex);

                continue;
            }

            throw new InvalidArgumentException("Invalid character found in expression: '{$char}'");
        }

        return $elements;
    }

    /**
     * Checks if a character is part of a number (digit or decimal point).
     */
    private function isNumberComponent(string $char): bool
    {
        return is_numeric($char) || $char === '.';
    }

    /**
     * Checks if a character is a valid mathematical operator.
     */
    private function isOperator(string $char): bool
    {
        return in_array($char, ['+', '-', '*', '/'], true);
    }

    /**
     * Extracts a full number sequence starting from the current index.
     */
    private function extractNumber(string $expression, int &$currentIndex): string
    {
        $numberStr = '';
        $length = strlen($expression);

        while ($currentIndex < $length && $this->isNumberComponent($expression[$currentIndex])) {
            $numberStr .= $expression[$currentIndex];
            $currentIndex++;
        }

        return $numberStr;
    }

    /**
     * Extracts an operator or a negative number if a stray negative sign is detected.
     *
     * @throws InvalidArgumentException
     */
    private function extractOperatorOrNegativeNumber(string $expression, ?string $previousElement, int &$currentIndex): string
    {
        $char = $expression[$currentIndex];

        if ($char === '-') {
            $isNegativeSign = $previousElement === null || $this->isOperator($previousElement);

            if ($isNegativeSign) {
                $currentIndex++;
                $numberStr = '-' . $this->extractNumber($expression, $currentIndex);

                if ($numberStr === '-') {
                    throw new InvalidArgumentException('Invalid expression: stray negative sign.');
                }

                return $numberStr;
            }
        }

        $currentIndex++;

        return $char;
    }

    /**
     * Reorders elements based on standard mathematical priorities using the Shunting-yard algorithm.
     * (Converts infix notation to postfix/Reverse Polish notation internally).
     *
     * @param  array<int, string>  $elements
     * @return array<int, string>
     */
    private function reorderElementsByPriority(array $elements): array
    {
        $output = [];
        $operators = [];

        $operatorPriorities = [
            '+' => 1,
            '-' => 1,
            '*' => 2,
            '/' => 2,
        ];

        foreach ($elements as $element) {
            if (is_numeric($element)) {
                $output[] = $element;
            } elseif (isset($operatorPriorities[$element])) {
                $currentPriority = $operatorPriorities[$element];

                // sets the order of operations by checking the priority of the last operator
                while ($this->shouldPopOperator($operators, $operatorPriorities, $currentPriority)) {
                    $output[] = array_pop($operators);
                }

                // pushes the current operator to the stack
                // makes sure that the operator is processed in the correct order
                $operators[] = $element;
            } else {
                throw new InvalidArgumentException("Unknown element: '{$element}'");
            }
        }

        // sets the order of operations by checking the priority of the last operator
        // this is done after the loop to ensure that all operators are processed
        while (!empty($operators)) {
            $output[] = array_pop($operators);
        }

        return $output;
    }

    /**
     * Determines whether the operator at the top of the stack should be popped off.
     *
     * @param array<int, string> $operators
     * @param array<string, int> $operatorPriorities
     * @param int $currentPriority
     */
    private function shouldPopOperator(array $operators, array $operatorPriorities, int $currentPriority): bool
    {
        if (empty($operators)) {
            return false;
        }

        $lastOperator = end($operators);

        if (!isset($operatorPriorities[$lastOperator])) {
            return false;
        }

        return $operatorPriorities[$lastOperator] >= $currentPriority;
    }

    /**
     * Evaluates the reordered elements to calculate the final result.
     *
     * @param  array<int, string>  $orderedElements
     */
    private function evaluateOrderedElements(array $orderedElements): float
    {
        $stack = [];

        foreach ($orderedElements as $element) {
            if (is_numeric($element)) {
                $stack[] = (float) $element;
            } else {
                if (count($stack) < 2) {
                    throw new InvalidArgumentException('Invalid expression format.');
                }

                $rightOperand = array_pop($stack);
                $leftOperand = array_pop($stack);

                $stack[] = match ($element) {
                    '+' => $leftOperand + $rightOperand,
                    '-' => $leftOperand - $rightOperand,
                    '*' => $leftOperand * $rightOperand,
                    '/' => $this->divide($leftOperand, $rightOperand),
                    default => throw new InvalidArgumentException("Unknown operator: '{$element}'"),
                };
            }
        }

        if (count($stack) !== 1) {
            throw new InvalidArgumentException('Invalid expression format: could not evaluate completely.');
        }

        return array_pop($stack);
    }

    /**
     * Perform division, ensuring no division by zero.
     *
     * @throws InvalidArgumentException
     */
    private function divide(float $operand1, float $operand2): float
    {
        if ($operand2 === 0.0) { // cannot forget this
            throw new InvalidArgumentException('Division by zero is not allowed.');
        }

        return $operand1 / $operand2;
    }
}
