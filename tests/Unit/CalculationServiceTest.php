<?php

namespace Tests\Unit;

use App\Services\CalculationService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CalculationServiceTest extends TestCase
{
    private CalculationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CalculationService;
    }

    public function test_simple_addition(): void
    {
        $this->assertEquals(3.0, $this->service->calculate('1 + 2'));
    }

    public function test_multiple_addition(): void
    {
        $this->assertEquals(8.0, $this->service->calculate('2 + 2 + 4'));
    }

    public function test_order_of_operations_multiplication(): void
    {
        $this->assertEquals(26.0, $this->service->calculate('2 + 4 * 6'));
    }

    public function test_negative_numbers_and_subtraction(): void
    {
        $this->assertEquals(-6.0, $this->service->calculate('6 - 2 + -2 * 5'));
    }

    public function test_complex_order_of_operations(): void
    {
        $this->assertEquals(8.0, $this->service->calculate('12 - 2 * 5 / 2.5'));
    }

    public function test_decimals_and_multiplication(): void
    {
        $this->assertEquals(66.82, $this->service->calculate('2.5 + 32 * 2.01'));
    }

    public function test_long_expression(): void
    {
        // 1 + 2 + 3 + (4 * 5 * 6) - 7 - (8 / 9)
        // 6 + 120 - 7 - 0.88888888888889
        // 126 - 7 - 0.88888888888889
        // 119 - 0.88888888888889 = 118.111111111
        $this->assertEqualsWithDelta(118.111111111, $this->service->calculate('1 + 2 + 3 + 4 * 5 * 6 - 7 - 8 / 9'), 0.00000001);
    }

    public function test_division_by_zero_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->calculate('5 / 0');
    }

    public function test_parenthesis_operations(): void
    {
        $this->assertEquals(14.0, $this->service->calculate('2 * (3 + 4)'));
        $this->assertEquals(2.0, $this->service->calculate('(8 - 4) / 2'));
        $this->assertEquals(10.0, $this->service->calculate('(2 + 3) * (4 - 2)'));
        $this->assertEquals(-10.0, $this->service->calculate('5 * (-2)'));
    }

    public function test_exponentiation_operations(): void
    {
        $this->assertEquals(8.0, $this->service->calculate('2 ^ 3'));
        $this->assertEquals(81.0, $this->service->calculate('3 ^ 4'));
        $this->assertEquals(512.0, $this->service->calculate('2 ^ 3 ^ 2'));
    }

    public function test_sqrt_function(): void
    {
        $this->assertEquals(4.0, $this->service->calculate('sqrt(16)'));
        $this->assertEquals(5.0, $this->service->calculate('sqrt(9 + 16)'));
        $this->assertEquals(3.0, $this->service->calculate('sqrt(81) / 3'));
    }

    public function test_user_provided_expression(): void
    {
        $this->assertEqualsWithDelta(31.5, $this->service->calculate('sqrt((((9*9)/12)+(13-4))*2)^2'), 0.00000001);
    }
}
