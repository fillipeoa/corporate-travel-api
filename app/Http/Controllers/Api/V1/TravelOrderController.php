<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TravelOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TravelOrder\IndexTravelOrderRequest;
use App\Http\Requests\TravelOrder\StoreTravelOrderRequest;
use App\Http\Requests\TravelOrder\UpdateTravelOrderStatusRequest;
use App\Http\Resources\TravelOrderResource;
use App\Models\TravelOrder;
use App\Notifications\TravelOrderStatusChanged;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TravelOrderController extends Controller
{
    /**
     * List travel orders with optional filters.
     * Regular users see only their own orders; admins see all.
     */
    public function index(IndexTravelOrderRequest $request): AnonymousResourceCollection
    {
        $query = TravelOrder::query()->with('user');

        if (! $request->user()->is_admin) {
            $query->where('user_id', $request->user()->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->validated('status'));
        }

        if ($request->filled('destination')) {
            $query->where('destination', 'like', '%'.$request->validated('destination').'%');
        }

        if ($request->filled('departure_from')) {
            $query->where('departure_date', '>=', $request->validated('departure_from'));
        }

        if ($request->filled('departure_to')) {
            $query->where('departure_date', '<=', $request->validated('departure_to'));
        }

        if ($request->filled('return_from')) {
            $query->where('return_date', '>=', $request->validated('return_from'));
        }

        if ($request->filled('return_to')) {
            $query->where('return_date', '<=', $request->validated('return_to'));
        }

        $orders = $query->latest()->paginate(15);

        return TravelOrderResource::collection($orders);
    }

    /**
     * Create a new travel order.
     */
    public function store(StoreTravelOrderRequest $request): JsonResponse
    {
        Gate::authorize('create', TravelOrder::class);

        $travelOrder = $request->user()->travelOrders()->create([
            'destination' => $request->validated('destination'),
            'departure_date' => $request->validated('departure_date'),
            'return_date' => $request->validated('return_date'),
            'status' => TravelOrderStatus::Requested,
        ]);

        $travelOrder->load('user');

        return (new TravelOrderResource($travelOrder))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Show a specific travel order.
     */
    public function show(TravelOrder $travelOrder): TravelOrderResource
    {
        Gate::authorize('view', $travelOrder);

        $travelOrder->load('user');

        return new TravelOrderResource($travelOrder);
    }

    /**
     * Update the status of a travel order.
     */
    public function updateStatus(UpdateTravelOrderStatusRequest $request, TravelOrder $travelOrder): JsonResponse
    {
        Gate::authorize('updateStatus', $travelOrder);

        $newStatus = TravelOrderStatus::from($request->validated('status'));

        if ($newStatus === TravelOrderStatus::Cancelled && $travelOrder->status === TravelOrderStatus::Approved) {
            return response()->json([
                'message' => 'Cannot cancel a travel order that has already been approved.',
            ], 422);
        }

        $travelOrder->update(['status' => $newStatus]);
        $travelOrder->load('user');

        $travelOrder->user->notify(new TravelOrderStatusChanged($travelOrder));

        return (new TravelOrderResource($travelOrder))
            ->response()
            ->setStatusCode(200);
    }
}
