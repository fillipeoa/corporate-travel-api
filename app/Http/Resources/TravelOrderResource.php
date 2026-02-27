<?php

namespace App\Http\Resources;

use App\Enums\TravelOrderStatus;
use App\Models\TravelOrder;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin TravelOrder */
class TravelOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Carbon $departureDate */
        $departureDate = $this->departure_date;
        /** @var Carbon $returnDate */
        $returnDate = $this->return_date;
        /** @var TravelOrderStatus $status */
        $status = $this->status;

        return [
            'id' => $this->id,
            'requester' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'destination' => $this->destination,
            'departure_date' => $departureDate->format('Y-m-d'),
            'return_date' => $returnDate->format('Y-m-d'),
            'status' => $status->value,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
