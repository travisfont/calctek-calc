<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalculationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_get_calculations()
    {
        $response = $this->getJson('/api/v1/calculations');

        $response->assertStatus(200);
        $this->assertArrayHasKey('x-guest-token', $response->headers->all());
    }

    public function test_guest_can_post_calculation()
    {
        $response = $this->postJson('/api/v1/calculations', [
            'expression' => '2+2'
        ]);

        $response->assertStatus(201);
        $this->assertArrayHasKey('x-guest-token', $response->headers->all());
        $this->assertEquals('4', $response->json('result'));
    }

    public function test_guest_can_delete_calculation()
    {
        // First, create a calculation to delete
        $postResponse = $this->postJson('/api/v1/calculations', [
            'expression' => '5+5'
        ]);

        $postResponse->assertStatus(201);
        $token = $postResponse->headers->get('x-guest-token');
        $calculationId = $postResponse->json('id');

        // Ensure calculation exists in database
        $this->assertDatabaseHas('calculations', [
            'id' => $calculationId
        ]);

        // Delete calculation
        $deleteResponse = $this->withHeaders([
            'x-guest-token' => $token
        ])->deleteJson('/api/v1/calculations/' . $calculationId);

        $deleteResponse->assertStatus(204);

        // Ensure calculation no longer exists
        $this->assertDatabaseMissing('calculations', [
            'id' => $calculationId
        ]);
    }

    public function test_guest_can_clear_calculations()
    {
        // Create first calculation
        $postResponse1 = $this->postJson('/api/v1/calculations', [
            'expression' => '1+1'
        ]);

        $token = $postResponse1->headers->get('x-guest-token');
        $id1 = $postResponse1->json('id');

        // Create second calculation using the same token
        $postResponse2 = $this->withHeaders([
            'x-guest-token' => $token
        ])->postJson('/api/v1/calculations', [
                    'expression' => '2+2'
                ]);

        $id2 = $postResponse2->json('id');

        // Ensure both calculations exist
        $this->assertDatabaseHas('calculations', ['id' => $id1]);
        $this->assertDatabaseHas('calculations', ['id' => $id2]);

        // Clear all calculations
        $clearResponse = $this->withHeaders([
            'x-guest-token' => $token
        ])->deleteJson('/api/v1/calculations');

        $clearResponse->assertStatus(204);

        // Ensure both calculations were deleted
        $this->assertDatabaseMissing('calculations', ['id' => $id1]);
        $this->assertDatabaseMissing('calculations', ['id' => $id2]);
    }
}
