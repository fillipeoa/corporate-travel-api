<?php

namespace Tests\Unit\Services;

use App\Enums\TravelOrderStatus;
use App\Events\TravelOrderStatusUpdated;
use App\Exceptions\TravelOrderAlreadyApprovedException;
use App\Models\TravelOrder;
use App\Models\User;
use App\Services\TravelOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TravelOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private TravelOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TravelOrderService::class);
    }

    public function test_create_returns_travel_order_with_requested_status(): void
    {
        $user = User::factory()->create();

        $travelOrder = $this->service->create($user, [
            'destination' => 'São Paulo',
            'departure_date' => '2026-04-01',
            'return_date' => '2026-04-05',
        ]);

        $this->assertInstanceOf(TravelOrder::class, $travelOrder);
        $this->assertEquals(TravelOrderStatus::Requested, $travelOrder->status);
        $this->assertEquals('São Paulo', $travelOrder->destination);
        $this->assertEquals($user->id, $travelOrder->user_id);
        $this->assertTrue($travelOrder->relationLoaded('user'));
    }

    public function test_update_status_dispatches_event(): void
    {
        Event::fake();

        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();
        $order = TravelOrder::factory()->for($user)->create();

        $this->service->updateStatus($order, TravelOrderStatus::Approved);

        Event::assertDispatched(TravelOrderStatusUpdated::class, function ($event) use ($order) {
            return $event->travelOrder->id === $order->id;
        });
    }

    public function test_update_status_throws_when_cancelling_approved_order(): void
    {
        $user = User::factory()->create();
        $order = TravelOrder::factory()->approved()->for($user)->create();

        $this->expectException(TravelOrderAlreadyApprovedException::class);

        $this->service->updateStatus($order, TravelOrderStatus::Cancelled);
    }

    public function test_cancel_sets_status_to_cancelled(): void
    {
        $user = User::factory()->create();
        $order = TravelOrder::factory()->for($user)->create();

        $result = $this->service->cancel($order);

        $this->assertEquals(TravelOrderStatus::Cancelled, $result->status);
        $this->assertDatabaseHas('travel_orders', [
            'id' => $order->id,
            'status' => TravelOrderStatus::Cancelled->value,
        ]);
    }

    public function test_list_scopes_orders_for_regular_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        TravelOrder::factory()->count(3)->for($user)->create();
        TravelOrder::factory()->count(2)->for($otherUser)->create();

        $result = $this->service->list($user, []);

        $this->assertCount(3, $result->items());
    }

    public function test_list_returns_all_orders_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        TravelOrder::factory()->count(2)->for($admin)->create();
        TravelOrder::factory()->count(3)->for($user)->create();

        $result = $this->service->list($admin, []);

        $this->assertCount(5, $result->items());
    }
}
