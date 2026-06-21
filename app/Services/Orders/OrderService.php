<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Services\Catalog\InventoryService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OrderService
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {
    }

    public function paginate(
        ?string $search = null,
        ?string $status = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return Order::query()
            ->withCount('items')
            ->when($search, function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('order_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_phone', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->latest('id')
            ->paginate($perPage);
    }

    public function show(Order $order): Order
    {
        return $order->load([
            'items.product:id,name,slug',
            'items.variant:id,product_id,age_label',
        ])->loadCount('items');
    }

    public function updateStatus(Order $order, string $newStatus): Order
    {
        if ($order->status === $newStatus) {
            return $this->show($order);
        }

        if ($order->status === 'cancelled' && $newStatus !== 'cancelled') {
            throw new RuntimeException('Cancelled orders cannot be reopened automatically.');
        }

        return DB::transaction(function () use ($order, $newStatus) {
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedOrder->load('items.variant');

            if ($newStatus === 'cancelled' && $lockedOrder->status !== 'cancelled') {
                foreach ($lockedOrder->items as $item) {
                    $this->inventoryService->returnCancelledStock(
                        variant: $item->variant,
                        quantity: (int) $item->quantity,
                        userId: auth()->id(),
                        notes: "Returned from cancelled order {$lockedOrder->order_number}",
                        referenceType: 'order',
                        referenceId: $lockedOrder->id,
                    );
                }
            }

            $lockedOrder->update([
                'status' => $newStatus,
            ]);

            return $this->show($lockedOrder->refresh());
        }, 5);
    }
}
