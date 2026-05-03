<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Http\Resources\Admin\ProductResource;
use App\Models\Product;
use App\Services\Catalog\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {
    }

    public function index(Request $request)
    {
        $products = $this->productService->paginate(
            search: $request->string('search')->toString(),
            status: $request->string('status')->toString() ?: null,
            categoryId: $request->filled('category_id') ? (int) $request->category_id : null,
            gender: $request->string('gender')->toString() ?: null,
            perPage: (int) $request->integer('per_page', 15),
        );

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => new ProductResource($product),
        ], 201);
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['category:id,name,slug'])
            ->load(['images' => function($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('sort_order', 'asc');
            }])
            ->loadCount(['variants', 'images']);

        return response()->json([
            'data' => new ProductResource($product),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->update($product, $request->validated());

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => new ProductResource($product),
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    public function archive(Product $product): JsonResponse
    {
        $product = $this->productService->archive($product);

        return response()->json([
            'message' => 'Product archived successfully.',
            'data' => new ProductResource($product),
        ]);
    }

    public function withVariants($productId): JsonResponse
    {
        $product = \App\Models\Product::with([
            'variants' => function($query) {
                $query->where('is_active', true)
                      ->with(['images' => function($imageQuery) {
                          $imageQuery->orderBy('is_primary', 'desc')
                                    ->orderBy('sort_order', 'asc');
                      }]);
            },
            'images' => function($query) {
                $query->whereNull('variant_id')
                      ->orderBy('is_primary', 'desc')
                      ->orderBy('sort_order', 'asc');
            }
        ])->findOrFail($productId);

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'variants' => $product->variants->map(function($variant) {
                    $primaryImage = $variant->images->where('is_primary', true)->first()
                        ?? $variant->images->first();
                    
                    return [
                        'id' => $variant->id,
                        'product_id' => $variant->product_id,
                        'sku' => $variant->sku,
                        'color_name' => $variant->color_name,
                        'size_name' => $variant->size_name,
                        'age_label' => $variant->age_label,
                        'price' => (float) $variant->price,
                        'compare_price' => $variant->compare_price ? (float) $variant->compare_price : null,
                        'quantity_on_hand' => (int) $variant->quantity_on_hand,
                        'quantity_reserved' => (int) $variant->quantity_reserved,
                        'available_quantity' => (int) ($variant->quantity_on_hand - $variant->quantity_reserved),
                        'is_active' => (bool) $variant->is_active,
                        'image' => $primaryImage ? \Storage::disk('public')->url($primaryImage->path) : null,
                        'images' => $variant->images->map(function($image) {
                            return [
                                'id' => $image->id,
                                'path' => $image->path,
                                'url' => \Storage::disk('public')->url($image->path),
                                'alt_text' => $image->alt_text,
                                'sort_order' => $image->sort_order,
                                'is_primary' => (bool) $image->is_primary,
                            ];
                        }),
                    ];
                })
            ]
        ]);
    }
}
