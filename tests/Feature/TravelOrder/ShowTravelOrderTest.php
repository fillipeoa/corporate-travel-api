<?php

namespace Tests\Feature\TravelOrder;

use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShowTravelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_view_own_travel_order(): void
    {
        $user = User::factory()->create();
        $travelOrder = TravelOrder::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/travel-orders/{$travelOrder->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $travelOrder->id)
            ->assertJsonPath('data.destination', $travelOrder->destination);
    }

    public function test_user_cannot_view_another_users_travel_order(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $travelOrder = TravelOrder::factory()->for($owner)->create();

        Sanctum::actingAs($otherUser);

        $response = $this->getJson("/api/v1/travel-orders/{$travelOrder->id}");

        $response->assertStatus(403);
    }

    public function test_admin_can_view_any_travel_order(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $travelOrder = TravelOrder::factory()->for($user)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/v1/travel-orders/{$travelOrder->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $travelOrder->id);
    }

    public function test_returns_404_for_nonexistent_travel_order(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->getJson('/api/v1/travel-orders/99999');

        $response->assertStatus(404);
    }

    public function test_show_fails_without_authentication(): void
    {
        $travelOrder = TravelOrder::factory()->create();

        $response = $this->getJson("/api/v1/travel-orders/{$travelOrder->id}");

        $response->assertStatus(401);
    }
}
