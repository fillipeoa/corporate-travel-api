<?php

namespace Tests\Feature\TravelOrder;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UpdateTravelOrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_approve_requested_order(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $travelOrder = TravelOrder::factory()->for($user)->create();

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/v1/travel-orders/{$travelOrder->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'approved');

        $this->assertDatabaseHas('travel_orders', [
            'id' => $travelOrder->id,
            'status' => TravelOrderStatus::Approved->value,
        ]);
    }

    public function test_admin_can_cancel_requested_order(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $travelOrder = TravelOrder::factory()->for($user)->create();

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/v1/travel-orders/{$travelOrder->id}/status", [
            'status' => 'cancelled',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_admin_cannot_cancel_approved_order(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $travelOrder = TravelOrder::factory()->approved()->for($user)->create();

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/v1/travel-orders/{$travelOrder->id}/status", [
            'status' => 'cancelled',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Cannot cancel a travel order that has already been approved.');
    }

    public function test_regular_user_cannot_update_own_order_status(): void
    {
        $user = User::factory()->create();
        $travelOrder = TravelOrder::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->patchJson("/api/v1/travel-orders/{$travelOrder->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_update_another_users_order_status(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $travelOrder = TravelOrder::factory()->for($owner)->create();

        Sanctum::actingAs($otherUser);

        $response = $this->patchJson("/api/v1/travel-orders/{$travelOrder->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_cannot_update_own_order_status(): void
    {
        $admin = User::factory()->admin()->create();
        $travelOrder = TravelOrder::factory()->for($admin)->create();

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/v1/travel-orders/{$travelOrder->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(403);
    }

    public function test_update_status_fails_with_invalid_status(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $travelOrder = TravelOrder::factory()->for($user)->create();

        Sanctum::actingAs($admin);

        $response = $this->patchJson("/api/v1/travel-orders/{$travelOrder->id}/status", [
            'status' => 'invalid-status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_update_status_fails_without_authentication(): void
    {
        $travelOrder = TravelOrder::factory()->create();

        $response = $this->patchJson("/api/v1/travel-orders/{$travelOrder->id}/status", [
            'status' => 'approved',
        ]);

        $response->assertStatus(401);
    }
}
