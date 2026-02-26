<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCalculationRequest;
use App\Models\Calculation;
use App\Services\CalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class CalculationController extends Controller
{
    public function __construct(
        private readonly CalculationService $calculationService
    ) {}

    /**
     * Display a listing of calculations.
     *
     * Example Request:
     * GET /api/v1/calculations
     *
     * Example Response:
     * HTTP/1.1 200 OK
     * [
     *     {
     *         "id": 1,
     *         "expression": "1 + 2",
     *         "result": 3,
     *         "created_at": "2026-02-26T12:00:00.000000Z",
     *         "updated_at": "2026-02-26T12:00:00.000000Z"
     *     }
     * ]
     */
    public function index(Request $request): JsonResponse
    {
        // latest() orders by created_at descending, get() retrieves all of them without a limit.
        return response()->json($request->user()->calculations()->latest()->get());
    }

    /**
     * Store a newly created calculation in the database.
     *
     * Example Request (matching CalculationServiceTest '1 + 2'):
     * POST /api/v1/calculations
     * Content-Type: application/json
     *
     * {
     *     "expression": "1 + 2"
     * }
     *
     * Example Response (Success):
     * HTTP/1.1 201 Created
     * {
     *     "id": 1,
     *     "expression": "1 + 2",
     *     "result": 3,
     *     "created_at": "2026-02-26T12:00:00.000000Z",
     *     "updated_at": "2026-02-26T12:00:00.000000Z"
     * }
     *
     * Example Request (matching CalculationServiceTest '5 / 0'):
     * POST /api/v1/calculations
     * Content-Type: application/json
     *
     * {
     *     "expression": "5 / 0"
     * }
     *
     * Example Response (Error - division by zero):
     * HTTP/1.1 422 Unprocessable Entity
     * {
     *     "message": "Division by zero"
     * }
     */
    public function store(StoreCalculationRequest $request): JsonResponse
    {
        $expression = (string) $request->validated('expression');

        try {
            $result = $this->calculationService->calculate($expression);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $calculation = $request->user()->calculations()->create([
            'expression' => $expression,
            'result' => $result,
        ]);

        return response()->json($calculation, 201);
    }

    /**
     * Remove the specified calculation from the database.
     *
     * Example Request:
     * DELETE /api/v1/calculations/1
     *
     * Example Response:
     * HTTP/1.1 204 No Content
     */
    public function destroy(string $id, Request $request): Response
    {
        $calculation = $request->user()->calculations()->findOrFail($id);
        $calculation->delete();

        return response()->noContent();
    }

    /**
     * Remove all calculations from the database.
     *
     * Example Request:
     * DELETE /api/v1/calculations
     *
     * Example Response:
     * HTTP/1.1 204 No Content
     */
    public function clear(Request $request): Response
    {
        $request->user()->calculations()->delete();

        return response()->noContent();
    }
}
