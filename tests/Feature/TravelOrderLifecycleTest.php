<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TravelOrderStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TravelOrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the complete travel order lifecycle:
     * register → login → create order → list → approve → notification → cancel blocked.
     */
    public function test_complete_travel_order_lifecycle(): void
    {
        $this->withoutMiddleware(ThrottleRequests::class);
        Notification::fake();

        // 1. Register a regular user via API
        $registerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $registerResponse->assertStatus(201)
            ->assertJsonStructure(['user', 'token']);

        // 2. User logs in via API
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'joao@example.com',
            'password' => 'secret123',
        ]);

        $loginResponse->assertOk()
            ->assertJsonStructure(['token']);

        // 3. User creates a travel order
        $user = User::where('email', 'joao@example.com')->first();
        Sanctum::actingAs($user);

        $createResponse = $this->postJson('/api/v1/travel-orders', [
            'destination' => 'São Paulo',
            'departure_date' => '2026-04-01',
            'return_date' => '2026-04-05',
        ]);

        $createResponse->assertStatus(201)
            ->assertJsonPath('data.status', 'requested')
            ->assertJsonPath('data.destination', 'São Paulo')
            ->assertJsonPath('data.requester.name', 'João Silva');

        $orderId = $createResponse->json('data.id');

        // 4. User lists their orders and sees the created order
        $listResponse = $this->getJson('/api/v1/travel-orders');

        $listResponse->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $orderId);

        // 5. Admin approves the order
        $admin = User::factory()->admin()->create();
        Sanctum::actingAs($admin);

        $approveResponse = $this->patchJson("/api/v1/travel-orders/{$orderId}/status", [
            'status' => 'approved',
        ]);

        $approveResponse->assertOk()
            ->assertJsonPath('data.status', 'approved');

        // 6. Verify the user received a notification
        Notification::assertSentTo($user, TravelOrderStatusChanged::class, function ($notification) use ($orderId) {
            return $notification->travelOrder->id === $orderId;
        });

        // 7. Verify the order cannot be cancelled after approval
        $cancelResponse = $this->patchJson("/api/v1/travel-orders/{$orderId}/status", [
            'status' => 'cancelled',
        ]);

        $cancelResponse->assertStatus(422)
            ->assertJsonPath('message', 'Cannot cancel a travel order that has already been approved.');

        // 8. User can still view the approved order
        Sanctum::actingAs($user);

        $showResponse = $this->getJson("/api/v1/travel-orders/{$orderId}");

        $showResponse->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }
}
