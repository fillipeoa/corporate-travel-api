<?php

namespace Tests\Feature\TravelOrder;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CancelTravelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_cancel_own_requested_order(): void
    {
        $user = User::factory()->create();
        $travelOrder = TravelOrder::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/travel-orders/{$travelOrder->id}/cancel");

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $this->assertDatabaseHas('travel_orders', [
            'id' => $travelOrder->id,
            'status' => TravelOrderStatus::Cancelled->value,
        ]);
    }

    public function test_user_cannot_cancel_approved_order(): void
    {
        $user = User::factory()->create();
        $travelOrder = TravelOrder::factory()->approved()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/travel-orders/{$travelOrder->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_user_cannot_cancel_another_users_order(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $travelOrder = TravelOrder::factory()->for($owner)->create();

        Sanctum::actingAs($otherUser);

        $response = $this->patchJson("/api/v1/travel-orders/{$travelOrder->id}/cancel");

        $response->assertStatus(403);
    }

    public function test_cancel_fails_without_authentication(): void
    {
        $travelOrder = TravelOrder::factory()->create();

        $response = $this->patchJson("/api/v1/travel-orders/{$travelOrder->id}/cancel");

        $response->assertStatus(401);
    }
}
