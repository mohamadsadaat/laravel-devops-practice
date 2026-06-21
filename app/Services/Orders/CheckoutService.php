<?php

namespace App\Services\Orders;

use App\Exceptions\InsufficientStockException;
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

            $productIds = $items->pluck('product_id')->unique()->values();
            $ageLabels = $items->pluck('age_label')->unique()->values();

            $variants = ProductVariant::query()
                ->with(['product.category:id,is_active', 'product:id,name,slug,status,base_price,category_id'])
                ->whereIn('product_id', $productIds)
                ->whereIn('age_label', $ageLabels)
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (ProductVariant $variant) => $this->variantLookupKey($variant->product_id, $variant->age_label));

            $shippingFee = (float) ($data['shipping_fee'] ?? 0);
            $subtotal = 0;

            foreach ($items as $item) {
                $variant = $variants->get($this->variantLookupKey((int) $item['product_id'], (string) $item['age_label']));

                if (!$variant) {
                    throw new RuntimeException('Selected age is not available for this product.');
                }

                if (!$variant->is_active) {
                    throw new RuntimeException("Selected age {$variant->age_label} is not active.");
                }

                if (
                    !$variant->product
                    || $variant->product->status !== 'active'
                    || !$variant->product->category
                    || !$variant->product->category->is_active
                ) {
                    throw new RuntimeException('Selected product is not active.');
                }

                if ($variant->available_quantity < (int) $item['quantity']) {
                    throw new InsufficientStockException(
                        $variant->age_label,
                        (int) $item['quantity'],
                        $variant->available_quantity
                    );
                }

                if (is_null($variant->product->base_price)) {
                    throw new RuntimeException('Selected product does not have a price.');
                }

                $subtotal += round(((float) $variant->product->base_price * (int) $item['quantity']), 2);
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
                $variant = $variants->get($this->variantLookupKey((int) $item['product_id'], (string) $item['age_label']));
                $quantity = (int) $item['quantity'];

                $unitPrice = (float) $variant->product->base_price;
                $lineTotal = round($unitPrice * $quantity, 2);

                $order->items()->create([
                    'product_id' => $variant->product_id,
                    'variant_id' => $variant->id,
                    'product_name_snapshot' => $variant->product->name,
                    'variant_snapshot' => $variant->age_label,
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
                'items.variant:id,product_id,age_label',
            ])->loadCount('items');
        }, 5);
    }

    private function variantLookupKey(int $productId, string $ageLabel): string
    {
        return $productId . '|' . mb_strtolower(trim($ageLabel));
    }

    private function generateOrderNumber(): string
    {
        do {
            $orderNumber = 'ORD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
        } while (Order::query()->where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }
}
