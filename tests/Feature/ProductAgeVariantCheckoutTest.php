<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAgeVariantCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_only_include_active_in_stock_products_and_variants(): void
    {
        $category = Category::create([
            'name' => 'Active Category',
            'slug' => 'active-category',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cotton Set',
            'slug' => 'cotton-set',
            'status' => 'active',
            'base_price' => 25,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'COT-2-4',
            'age_label' => '2-4 years',
            'quantity_on_hand' => 5,
            'quantity_reserved' => 0,
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'COT-4-6',
            'age_label' => '4-6 years',
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'is_active' => true,
        ]);

        $soldOutProduct = Product::create([
            'category_id' => $category->id,
            'name' => 'Sold Out Set',
            'slug' => 'sold-out-set',
            'status' => 'active',
            'base_price' => 30,
        ]);

        ProductVariant::create([
            'product_id' => $soldOutProduct->id,
            'sku' => 'OUT-2-4',
            'age_label' => '2-4 years',
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'is_active' => true,
        ]);

        $this->getJson('/api/admin/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $product->id);

        $this->getJson("/api/admin/products/{$product->id}/with-variants")
            ->assertOk()
            ->assertJsonCount(1, 'product.variants')
            ->assertJsonPath('product.variants.0.age_label', '2-4 years');
    }

    public function test_checkout_sells_product_by_age_and_decreases_variant_quantity(): void
    {
        $category = Category::create([
            'name' => 'Active Category',
            'slug' => 'active-category',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Baby Hoodie',
            'slug' => 'baby-hoodie',
            'status' => 'active',
            'base_price' => 40,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'HOO-1-2',
            'age_label' => '1-2 years',
            'quantity_on_hand' => 6,
            'quantity_reserved' => 0,
            'is_active' => true,
        ]);

        $this->postJson('/api/checkout', [
            'customer_name' => 'Test Customer',
            'customer_phone' => '0999999999',
            'customer_address' => 'Test address',
            'items' => [
                [
                    'product_id' => $product->id,
                    'age_label' => '1-2 years',
                    'quantity' => 2,
                ],
            ],
        ])
            ->assertCreated()
            ->assertJsonPath('data.subtotal', '80.00')
            ->assertJsonPath('data.items.0.age_label_snapshot', '1-2 years');

        $this->assertSame(4, $variant->refresh()->quantity_on_hand);
    }

    public function test_checkout_fails_when_insufficient_stock(): void
    {
        $category = Category::create([
            'name' => 'Active Category',
            'slug' => 'active-category',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Limited Stock Product',
            'slug' => 'limited-stock-product',
            'status' => 'active',
            'base_price' => 50,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'LIM-3-4',
            'age_label' => '3-4 years',
            'quantity_on_hand' => 2,
            'quantity_reserved' => 0,
            'is_active' => true,
        ]);

        // Try to order more than available stock
        $response = $this->postJson('/api/checkout', [
            'customer_name' => 'Test Customer',
            'customer_phone' => '0999999999',
            'customer_address' => 'Test address',
            'items' => [
                [
                    'product_id' => $product->id,
                    'age_label' => '3-4 years',
                    'quantity' => 5, // Requesting 5 but only 2 available
                ],
            ],
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment([
                'message' => 'Insufficient stock for age 3-4 years.'
            ]);

        // Verify stock remains unchanged
        $this->assertSame(2, $variant->refresh()->quantity_on_hand);
    }

    public function test_concurrent_checkout_requests_handle_insufficient_stock(): void
    {
        $category = Category::create([
            'name' => 'Active Category',
            'slug' => 'active-category',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Concurrent Test Product',
            'slug' => 'concurrent-test-product',
            'status' => 'active',
            'base_price' => 30,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'CON-2-3',
            'age_label' => '2-3 years',
            'quantity_on_hand' => 3,
            'quantity_reserved' => 0,
            'is_active' => true,
        ]);

        $checkoutData = [
            'customer_name' => 'Test Customer',
            'customer_phone' => '0999999999',
            'customer_address' => 'Test address',
            'items' => [
                [
                    'product_id' => $product->id,
                    'age_label' => '2-3 years',
                    'quantity' => 2,
                ],
            ],
        ];

        // First request should succeed
        $firstResponse = $this->postJson('/api/checkout', $checkoutData);
        $firstResponse->assertCreated();

        // Second request should fail due to insufficient stock (only 1 left, requesting 2)
        $secondResponse = $this->postJson('/api/checkout', $checkoutData);
        $secondResponse->assertStatus(500)
            ->assertJsonFragment([
                'message' => 'Insufficient stock for age 2-3 years.'
            ]);

        // Verify final stock is 1 (3 - 2 = 1)
        $this->assertSame(1, $variant->refresh()->quantity_on_hand);
    }
}
