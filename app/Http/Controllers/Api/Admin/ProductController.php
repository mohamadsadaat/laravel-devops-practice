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
use Illuminate\Support\Facades\Storage;

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
        $product = Product::query()
            ->whereKey($product->id)
            ->where('status', 'active')
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->whereHas('variants', fn ($query) => $query
                ->where('is_active', true)
                ->whereColumn('quantity_on_hand', '>', 'quantity_reserved'))
            ->with(['category:id,name,slug'])
            ->with(['images' => function ($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('sort_order', 'asc');
            }])
            ->withCount([
                'variants as variants_count' => fn ($query) => $query
                    ->where('is_active', true)
                    ->whereColumn('quantity_on_hand', '>', 'quantity_reserved'),
                'images',
            ])
            ->firstOrFail();

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
        $product = Product::query()
            ->whereKey($productId)
            ->where('status', 'active')
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->whereHas('variants', fn ($query) => $query
                ->where('is_active', true)
                ->whereColumn('quantity_on_hand', '>', 'quantity_reserved'))
            ->with([
                'category:id,name,slug',
                'variants' => function ($query) {
                    $query->where('is_active', true)
                        ->whereColumn('quantity_on_hand', '>', 'quantity_reserved')
                        ->orderBy('age_label');
                },
                'images' => function ($query) {
                    $query->orderBy('is_primary', 'desc')
                        ->orderBy('sort_order', 'asc');
                },
            ])
            ->firstOrFail();

        return response()->json([
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'base_price' => !is_null($product->base_price) ? (float) $product->base_price : null,
                'category' => [
                    'id' => $product->category?->id,
                    'name' => $product->category?->name,
                    'slug' => $product->category?->slug,
                ],
                'primary_image_url' => $this->primaryImageUrl($product),
                'images' => $product->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'path' => $image->path,
                        'url' => Storage::disk('public')->url($image->path),
                        'alt_text' => $image->alt_text,
                        'sort_order' => $image->sort_order,
                        'is_primary' => (bool) $image->is_primary,
                    ];
                }),
                'variants' => $product->variants->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'product_id' => $variant->product_id,
                        'age_label' => $variant->age_label,
                        'quantity_on_hand' => (int) $variant->quantity_on_hand,
                        'available_quantity' => (int) $variant->available_quantity,
                        'is_active' => (bool) $variant->is_active,
                    ];
                }),
            ],
        ]);
    }

    private function primaryImageUrl(Product $product): ?string
    {
        $primaryImage = $product->images->where('is_primary', true)->first()
            ?? $product->images->first();

        return $primaryImage ? Storage::disk('public')->url($primaryImage->path) : null;
    }
}
