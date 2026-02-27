<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\TravelOrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TravelOrder\IndexTravelOrderRequest;
use App\Http\Requests\TravelOrder\StoreTravelOrderRequest;
use App\Http\Requests\TravelOrder\UpdateTravelOrderStatusRequest;
use App\Http\Resources\TravelOrderResource;
use App\Models\TravelOrder;
use App\Services\TravelOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class TravelOrderController extends Controller
{
    public function __construct(
        private readonly TravelOrderService $travelOrderService,
    ) {}

    /**
     * List travel orders with optional filters.
     */
    public function index(IndexTravelOrderRequest $request): AnonymousResourceCollection
    {
        $orders = $this->travelOrderService->list(
            $request->user(),
            $request->validated(),
        );

        return TravelOrderResource::collection($orders);
    }

    /**
     * Create a new travel order.
     */
    public function store(StoreTravelOrderRequest $request): JsonResponse
    {
        Gate::authorize('create', TravelOrder::class);

        $travelOrder = $this->travelOrderService->create(
            $request->user(),
            $request->validated(),
        );

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
     * Update the status of a travel order (admin action).
     */
    public function updateStatus(UpdateTravelOrderStatusRequest $request, TravelOrder $travelOrder): JsonResponse
    {
        Gate::authorize('updateStatus', $travelOrder);

        $travelOrder = $this->travelOrderService->updateStatus(
            $travelOrder,
            TravelOrderStatus::from($request->validated('status')),
        );

        return (new TravelOrderResource($travelOrder))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Cancel a travel order (requester action).
     */
    public function cancel(TravelOrder $travelOrder): JsonResponse
    {
        Gate::authorize('cancel', $travelOrder);

        $travelOrder = $this->travelOrderService->cancel($travelOrder);

        return (new TravelOrderResource($travelOrder))
            ->response()
            ->setStatusCode(200);
    }
}
