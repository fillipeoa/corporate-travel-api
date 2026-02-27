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
        $event->travelOrder->user->notify(
            new TravelOrderStatusChanged($event->travelOrder),
        );
    }
}
