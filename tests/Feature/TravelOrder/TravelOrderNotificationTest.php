<?php

namespace Tests\Feature\TravelOrder;

use App\Models\TravelOrder;
use App\Models\User;
use App\Notifications\TravelOrderStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TravelOrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_sent_on_approval(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $travelOrder = TravelOrder::factory()->for($user)->create();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/travel-orders/{$travelOrder->id}/status", [
            'status' => 'approved',
        ]);

        Notification::assertSentTo($user, TravelOrderStatusChanged::class, function ($notification) use ($travelOrder) {
            return $notification->travelOrder->id === $travelOrder->id;
        });
    }

    public function test_notification_sent_on_cancellation(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $travelOrder = TravelOrder::factory()->for($user)->create();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/travel-orders/{$travelOrder->id}/status", [
            'status' => 'cancelled',
        ]);

        Notification::assertSentTo($user, TravelOrderStatusChanged::class, function ($notification) use ($travelOrder) {
            return $notification->travelOrder->id === $travelOrder->id;
        });
    }

    public function test_notification_not_sent_when_cancellation_rejected(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $travelOrder = TravelOrder::factory()->approved()->for($user)->create();

        Sanctum::actingAs($admin);

        $this->patchJson("/api/v1/travel-orders/{$travelOrder->id}/status", [
            'status' => 'cancelled',
        ]);

        Notification::assertNothingSent();
    }
}
