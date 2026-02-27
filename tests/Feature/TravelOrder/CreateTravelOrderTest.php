<?php

namespace Tests\Feature\TravelOrder;

use App\Enums\TravelOrderStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateTravelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_travel_order_with_valid_data(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/travel-orders', [
            'destination' => 'São Paulo',
            'departure_date' => now()->addDays(7)->format('Y-m-d'),
            'return_date' => now()->addDays(14)->format('Y-m-d'),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.destination', 'São Paulo')
            ->assertJsonPath('data.status', TravelOrderStatus::Requested->value)
            ->assertJsonPath('data.requester.id', $user->id);

        $this->assertDatabaseHas('travel_orders', [
            'user_id' => $user->id,
            'destination' => 'São Paulo',
            'status' => TravelOrderStatus::Requested->value,
        ]);
    }

    public function test_status_defaults_to_requested(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/travel-orders', [
            'destination' => 'Rio de Janeiro',
            'departure_date' => now()->addDays(3)->format('Y-m-d'),
            'return_date' => now()->addDays(10)->format('Y-m-d'),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'requested');
    }

    public function test_create_fails_with_invalid_data(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/travel-orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['destination', 'departure_date', 'return_date']);
    }

    public function test_create_fails_when_return_date_is_before_departure(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/travel-orders', [
            'destination' => 'Brasília',
            'departure_date' => now()->addDays(10)->format('Y-m-d'),
            'return_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['return_date']);
    }

    public function test_create_fails_with_past_departure_date(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/v1/travel-orders', [
            'destination' => 'Curitiba',
            'departure_date' => now()->subDays(1)->format('Y-m-d'),
            'return_date' => now()->addDays(5)->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['departure_date']);
    }

    public function test_create_fails_without_authentication(): void
    {
        $response = $this->postJson('/api/v1/travel-orders', [
            'destination' => 'São Paulo',
            'departure_date' => now()->addDays(7)->format('Y-m-d'),
            'return_date' => now()->addDays(14)->format('Y-m-d'),
        ]);

        $response->assertStatus(401);
    }
}
