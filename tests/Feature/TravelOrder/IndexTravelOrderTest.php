<?php

namespace Tests\Feature\TravelOrder;

use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class IndexTravelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_only_own_travel_orders(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        TravelOrder::factory()->count(3)->for($user)->create();
        TravelOrder::factory()->count(2)->for($otherUser)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/travel-orders');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_sees_all_travel_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        TravelOrder::factory()->count(2)->for($admin)->create();
        TravelOrder::factory()->count(3)->for($user)->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/travel-orders');

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_filter_by_status(): void
    {
        $user = User::factory()->create();

        TravelOrder::factory()->count(2)->for($user)->create();
        TravelOrder::factory()->approved()->for($user)->create();

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/travel-orders?status=approved');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_by_destination(): void
    {
        $user = User::factory()->create();

        TravelOrder::factory()->for($user)->create(['destination' => 'São Paulo']);
        TravelOrder::factory()->for($user)->create(['destination' => 'Rio de Janeiro']);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/travel-orders?destination=Paulo');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_by_departure_date_range(): void
    {
        $user = User::factory()->create();

        TravelOrder::factory()->for($user)->create([
            'departure_date' => '2026-03-10',
            'return_date' => '2026-03-20',
        ]);
        TravelOrder::factory()->for($user)->create([
            'departure_date' => '2026-04-10',
            'return_date' => '2026-04-20',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/travel-orders?departure_from=2026-03-01&departure_to=2026-03-31');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_filter_by_created_date_range(): void
    {
        $user = User::factory()->create();

        $oldOrder = TravelOrder::factory()->for($user)->create([
            'departure_date' => '2026-05-01',
            'return_date' => '2026-05-10',
        ]);
        $oldOrder->update(['created_at' => '2026-01-15 10:00:00']);

        TravelOrder::factory()->for($user)->create([
            'departure_date' => '2026-05-15',
            'return_date' => '2026-05-25',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/travel-orders?created_from=2026-02-01&created_to=2026-12-31');

        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_index_fails_without_authentication(): void
    {
        $response = $this->getJson('/api/v1/travel-orders');

        $response->assertStatus(401);
    }
}
