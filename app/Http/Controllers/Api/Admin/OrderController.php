<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\Admin\OrderResource;
use App\Models\Order;
use App\Services\Orders\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {
    }

    public function index(Request $request)
    {
        $orders = $this->orderService->paginate(
            search: $request->string('search')->toString(),
            status: $request->string('status')->toString() ?: null,
            perPage: (int) $request->integer('per_page', 15),
        );

        return OrderResource::collection($orders);
    }

    public function show(Order $order): JsonResponse
    {
        return response()->json([
            'data' => new OrderResource($this->orderService->show($order)),
        ]);
    }

    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order
    ): JsonResponse {
        $order = $this->orderService->updateStatus(
            $order,
            $request->string('status')->toString(),
        );

        return response()->json([
            'message' => 'Order status updated successfully.',
            'data' => new OrderResource($order),
        ]);
    }

    public function activeCount(): JsonResponse
    {
        $activeCount = Order::query()
            ->whereNotIn('status', ['delivered', 'cancelled'])
            ->count();

        $deliveredCount = Order::query()
            ->where('status', 'delivered')
            ->count();

        $cancelledCount = Order::query()
            ->where('status', 'cancelled')
            ->count();

        $totalCount = Order::query()->count();

        return response()->json([
            'active_orders_count' => $activeCount,
            'delivered_orders_count' => $deliveredCount,
            'cancelled_orders_count' => $cancelledCount,
            'total_orders_count' => $totalCount,
        ]);
    }
}