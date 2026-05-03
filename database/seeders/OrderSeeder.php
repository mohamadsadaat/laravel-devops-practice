<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\Catalog\InventoryService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $inventoryService = app(InventoryService::class);

        // Get available variants
        $variants = ProductVariant::with('product')
            ->where('is_active', true)
            ->whereHas('product', fn($q) => $q->where('status', 'active'))
            ->get();

        if ($variants->isEmpty()) {
            $this->command->warn('No active variants found. Please run SampleDataSeeder first.');
            return;
        }

        $orderStatuses = ['pending', 'confirmed', 'preparing', 'shipped', 'delivered', 'cancelled'];
        
        for ($i = 1; $i <= 10; $i++) {
            DB::transaction(function () use ($faker, $variants, $inventoryService, $orderStatuses, $i) {
                // Generate random items for the order
                $orderItems = collect();
                $subtotal = 0;
                $itemCount = $faker->numberBetween(1, 4);

                // Select random variants
                $selectedVariants = $variants->random($itemCount > $variants->count() ? $variants->count() : $itemCount);

                foreach ($selectedVariants as $variant) {
                    $quantity = $faker->numberBetween(1, 3);
                    $unitPrice = (float) $variant->price;
                    $lineTotal = round($unitPrice * $quantity, 2);
                    $subtotal += $lineTotal;

                    $orderItems->push([
                        'product_id' => $variant->product_id,
                        'variant_id' => $variant->id,
                        'product_name_snapshot' => $variant->product->name,
                        'variant_snapshot' => implode(' / ', array_filter([
                            $variant->color_name,
                            $variant->size_name,
                            $variant->age_label,
                        ])),
                        'sku_snapshot' => $variant->sku,
                        'unit_price' => $unitPrice,
                        'quantity' => $quantity,
                        'line_total' => $lineTotal,
                    ]);
                }

                $shippingFee = $faker->randomFloat(2, 0, 20);
                $total = round($subtotal + $shippingFee, 2);

                // Determine status (less chance of cancelled orders)
                $status = $faker->randomElement([
                    ...array_fill(0, 3, 'pending'),
                    ...array_fill(0, 2, 'confirmed'),
                    ...array_fill(0, 2, 'preparing'),
                    ...array_fill(0, 2, 'shipped'),
                    ...array_fill(0, 3, 'delivered'),
                    ...array_fill(0, 1, 'cancelled'),
                ]);

                // Create order
                $order = Order::create([
                    'order_number' => 'ORD-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
                    'customer_name' => $faker->name,
                    'customer_phone' => $faker->phoneNumber,
                    'customer_address' => $faker->address,
                    'city' => $faker->city,
                    'notes' => $faker->optional(0.7)->sentence,
                    'status' => $status,
                    'subtotal' => $subtotal,
                    'shipping_fee' => $shippingFee,
                    'total' => $total,
                    'placed_at' => $faker->dateTimeBetween('-1 month', 'now'),
                ]);

                // Create order items
                foreach ($orderItems as $item) {
                    $order->items()->create($item);

                    // Update stock only for non-cancelled orders
                    if ($status !== 'cancelled') {
                        try {
                            $inventoryService->sellStock(
                                variant: $variants->find($item['variant_id']),
                                quantity: $item['quantity'],
                                userId: null,
                                notes: "Sold via order {$order->order_number}",
                                referenceType: 'order',
                                referenceId: $order->id,
                            );
                        } catch (\Exception $e) {
                            // If stock is insufficient, skip this item
                            $this->command->warn("Insufficient stock for variant {$item['variant_id']} in order {$order->order_number}");
                        }
                    }
                }

                $this->command->info("Created order #{$i}: {$order->order_number} ({$status})");
            });
        }

        $this->command->info('Successfully created 10 sample orders!');
    }
}
