<?php

namespace App\Services;

use App\Abstract\DivideTrait;
use App\Abstract\IsNumberComponentTrait;
use App\Abstract\IsOperatorTrait;
use InvalidArgumentException;

class CalculationService
{
    use DivideTrait, IsNumberComponentTrait, IsOperatorTrait;
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

            if ($this->isOperator($char) || $char === '(' || $char === ')') {
                $previousElement = empty($elements) ? null : end($elements);
                $elements[] = $this->extractOperatorOrNegativeNumber($expression, $previousElement, $currentIndex);

                continue;
            }

            if (ctype_alpha($char)) {
                $elements[] = $this->extractFunction($expression, $currentIndex);

                continue;
            }

            throw new InvalidArgumentException("Invalid character found in expression: '{$char}'");
        }

        return $elements;
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
            $isNegativeSign = $previousElement === null || $this->isOperator($previousElement) || $previousElement === '(';

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
     * Extracts a mathematical function name.
     *
     * @throws InvalidArgumentException
     */
    private function extractFunction(string $expression, int &$currentIndex): string
    {
        $functionStr = '';
        $length = strlen($expression);

        while ($currentIndex < $length && ctype_alpha($expression[$currentIndex])) {
            $functionStr .= $expression[$currentIndex];
            $currentIndex++;
        }

        if (!in_array($functionStr, ['sqrt'])) {
            throw new InvalidArgumentException("Unknown function: '{$functionStr}'");
        }

        return $functionStr;
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
            '^' => 3,
        ];

        $rightAssociative = ['^'];

        foreach ($elements as $element) {
            if (is_numeric($element)) {
                $output[] = $element;
            } elseif (in_array($element, ['sqrt', '('], true)) {
                $operators[] = $element;
            } elseif ($element === ')') {
                while (!empty($operators) && end($operators) !== '(') {
                    $output[] = array_pop($operators);
                }

                if (empty($operators)) {
                    throw new InvalidArgumentException('Mismatched parenthesis.');
                }

                array_pop($operators); // Discard '('

                if (!empty($operators) && end($operators) === 'sqrt') {
                    $output[] = array_pop($operators);
                }
            } elseif (isset($operatorPriorities[$element])) {
                $currentPriority = $operatorPriorities[$element];
                $isRightAssociative = in_array($element, $rightAssociative, true);

                // sets the order of operations by checking the priority of the last operator
                while ($this->shouldPopOperator($operators, $operatorPriorities, $currentPriority, $isRightAssociative)) {
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
            $op = array_pop($operators);
            if ($op === '(' || $op === ')') {
                throw new InvalidArgumentException('Mismatched parenthesis.');
            }
            $output[] = $op;
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
    private function shouldPopOperator(array $operators, array $operatorPriorities, int $currentPriority, bool $isRightAssociative = false): bool
    {
        if (empty($operators)) {
            return false;
        }

        $lastOperator = end($operators);

        if (!isset($operatorPriorities[$lastOperator])) {
            return false;
        }

        if ($isRightAssociative) {
            return $operatorPriorities[$lastOperator] > $currentPriority;
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
            } elseif ($element === 'sqrt') {
                if (empty($stack)) {
                    throw new InvalidArgumentException('Invalid expression format.');
                }
                $operand = array_pop($stack);
                if ($operand < 0) {
                    throw new InvalidArgumentException('Square root of negative number is not allowed.');
                }
                $stack[] = sqrt($operand);
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
                    '^' => pow($leftOperand, $rightOperand),
                    default => throw new InvalidArgumentException("Unknown operator: '{$element}'"),
                };
            }
        }

        if (count($stack) !== 1) {
            throw new InvalidArgumentException('Invalid expression format: could not evaluate completely.');
        }

        return array_pop($stack);
    }
}
