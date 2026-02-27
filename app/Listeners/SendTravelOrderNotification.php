<?php

namespace App\Listeners;

use App\Events\TravelOrderStatusUpdated;
use App\Notifications\TravelOrderStatusChanged;

class SendTravelOrderNotification
{
    /**
     * Handle the event.
     */
    public function handle(TravelOrderStatusUpdated $event): void
    {
        /** @var \App\Models\User $user */
        $user = $event->travelOrder->user;

        $user->notify(
            new TravelOrderStatusChanged($event->travelOrder),
        );
    }
}
