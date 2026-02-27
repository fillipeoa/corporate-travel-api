<?php

namespace App\Services;

use App\Enums\TravelOrderStatus;
use App\Events\TravelOrderStatusUpdated;
use App\Exceptions\TravelOrderAlreadyApprovedException;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TravelOrderService
{
    /**
     * List travel orders with optional filters.
     * Regular users see only their own; admins see all.
     *
     * @param  array<string, mixed>  $filters
     */
    public function list(User $user, array $filters): LengthAwarePaginator
    {
        $query = TravelOrder::query()->with('user');

        if (! $user->is_admin) {
            $query->where('user_id', $user->id);
        }

        $this->applyFilters($query, $filters);

        return $query->latest()->paginate(15);
    }

    /**
     * Create a new travel order for a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(User $user, array $data): TravelOrder
    {
        $travelOrder = $user->travelOrders()->create([
            'destination' => $data['destination'],
            'departure_date' => $data['departure_date'],
            'return_date' => $data['return_date'],
            'status' => TravelOrderStatus::Requested,
        ]);

        $travelOrder->load('user');

        return $travelOrder;
    }

    /**
     * Update the status of a travel order (admin action).
     *
     * @throws TravelOrderAlreadyApprovedException
     */
    public function updateStatus(TravelOrder $travelOrder, TravelOrderStatus $newStatus): TravelOrder
    {
        if ($newStatus === TravelOrderStatus::Cancelled && $travelOrder->status === TravelOrderStatus::Approved) {
            throw new TravelOrderAlreadyApprovedException;
        }

        $travelOrder->update(['status' => $newStatus]);
        $travelOrder->load('user');

        TravelOrderStatusUpdated::dispatch($travelOrder);

        return $travelOrder;
    }

    /**
     * Cancel a travel order (requester action).
     */
    public function cancel(TravelOrder $travelOrder): TravelOrder
    {
        $travelOrder->update(['status' => TravelOrderStatus::Cancelled]);
        $travelOrder->load('user');

        return $travelOrder;
    }

    /**
     * Apply filters to the travel order query.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<TravelOrder>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters($query, array $filters): void
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['destination'])) {
            $query->where('destination', 'like', '%'.$filters['destination'].'%');
        }

        if (! empty($filters['departure_from'])) {
            $query->where('departure_date', '>=', $filters['departure_from']);
        }

        if (! empty($filters['departure_to'])) {
            $query->where('departure_date', '<=', $filters['departure_to']);
        }

        if (! empty($filters['return_from'])) {
            $query->where('return_date', '>=', $filters['return_from']);
        }

        if (! empty($filters['return_to'])) {
            $query->where('return_date', '<=', $filters['return_to']);
        }

        if (! empty($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (! empty($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to'].' 23:59:59');
        }
    }
}
