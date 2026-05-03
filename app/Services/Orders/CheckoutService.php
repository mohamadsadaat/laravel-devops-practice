<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Services\Catalog\InventoryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CheckoutService
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {
    }

    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $items = collect($data['items']);

            $variantIds = $items->pluck('variant_id')->unique()->values();

            $variants = ProductVariant::query()
                ->with(['product:id,name,slug,status'])
                ->whereIn('id', $variantIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($variants->count() !== $variantIds->count()) {
                throw new RuntimeException('One or more selected variants were not found.');
            }

            $shippingFee = (float) ($data['shipping_fee'] ?? 0);
            $subtotal = 0;

            foreach ($items as $item) {
                $variant = $variants->get((int) $item['variant_id']);

                if (!$variant) {
                    throw new RuntimeException('Variant not found.');
                }

                if (!$variant->is_active) {
                    throw new RuntimeException("Variant {$variant->sku} is not active.");
                }

                if (!$variant->product || $variant->product->status !== 'active') {
                    throw new RuntimeException("Product for variant {$variant->sku} is not active.");
                }

                $subtotal += round(((float) $variant->price * (int) $item['quantity']), 2);
            }

            $total = round($subtotal + $shippingFee, 2);

            $order = Order::query()->create([
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_address' => $data['customer_address'],
                'city' => $data['city'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total' => $total,
                'placed_at' => Carbon::now(),
            ]);

            foreach ($items as $item) {
                $variant = $variants->get((int) $item['variant_id']);
                $quantity = (int) $item['quantity'];

                $variantSnapshot = implode(' / ', [
                    $variant->color_name,
                    $variant->size_name,
                    $variant->age_label,
                ]);

                $unitPrice = (float) $variant->price;
                $lineTotal = round($unitPrice * $quantity, 2);

                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'product_name_snapshot' => $variant->product->name,
                    'variant_snapshot' => $variantSnapshot,
                    'sku_snapshot' => $variant->sku,
                    'unit_price' => $unitPrice,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ]);

                $this->inventoryService->sellStock(
                    variant: $variant,
                    quantity: $quantity,
                    userId: null,
                    notes: "Sold via checkout {$order->order_number}",
                    referenceType: 'order',
                    referenceId: $order->id,
                );
            }

            return $order->load([
                'items.product:id,name,slug',
                'items.variant:id,product_id,sku,color_name,size_name,age_label',
            ])->loadCount('items');
        }, 5);
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}