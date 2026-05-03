<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductSalesController extends Controller
{
    public function index(): JsonResponse
    {
        // Get all products with their variants
        $products = Product::with(['variants'])
            ->where('status', 'active')
            ->get()
            ->map(function ($product) {
                // Get total sold for each variant
                $variants = $product->variants->map(function ($variant) {
                    $totalSold = OrderItem::where('variant_id', $variant->id)
                        ->sum('quantity');
                    
                    return [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'color_name' => $variant->color_name,
                        'size_name' => $variant->size_name,
                        'age_label' => $variant->age_label,
                        'price' => $variant->price,
                        'quantity_on_hand' => $variant->quantity_on_hand,
                        'quantity_reserved' => $variant->quantity_reserved,
                        'available_quantity' => $variant->quantity_on_hand - $variant->quantity_reserved,
                        'total_sold' => (int) $totalSold,
                    ];
                });

                // Calculate total sold for the product
                $productTotalSold = $variants->sum('total_sold');
                
                // Calculate total remaining for the product
                $productTotalRemaining = $variants->sum('available_quantity');

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'base_price' => $product->base_price,
                    'brand' => $product->brand,
                    'gender' => $product->gender,
                    'status' => $product->status,
                    'total_sold' => $productTotalSold,
                    'total_remaining' => $productTotalRemaining,
                    'variants_count' => $variants->count(),
                    'variants' => $variants,
                ];
            });

        // Calculate overall statistics
        $totalProducts = $products->count();
        $totalVariants = $products->sum('variants_count');
        $totalSold = $products->sum('total_sold');
        $totalRemaining = $products->sum('total_remaining');

        return response()->json([
            'data' => $products,
            'summary' => [
                'total_products' => $totalProducts,
                'total_variants' => $totalVariants,
                'total_items_sold' => $totalSold,
                'total_items_remaining' => $totalRemaining,
            ]
        ]);
    }
}
