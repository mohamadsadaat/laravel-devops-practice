<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductVariantRequest;
use App\Http\Requests\Admin\UpdateProductVariantRequest;
use App\Http\Resources\Admin\ProductVariantResource;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductVariantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function __construct(
        private readonly ProductVariantService $productVariantService
    ) {
    }

    public function globalIndex(Request $request)
    {
        $variants = $this->productVariantService->paginateAll(
            search: $request->string('search')->toString(),
            isActive: $request->filled('is_active')
                ? $request->boolean('is_active')
                : null,
            perPage: (int) $request->integer('per_page', 15),
        );

        return ProductVariantResource::collection($variants);
    }

    public function index(Request $request, Product $product)
    {
        $variants = $this->productVariantService->paginateByProduct(
            product: $product,
            search: $request->string('search')->toString(),
            perPage: (int) $request->integer('per_page', 15),
        );

        return ProductVariantResource::collection($variants);
    }

    public function store(StoreProductVariantRequest $request, Product $product): JsonResponse
    {
        $variant = $this->productVariantService->create($product, $request->validated());

        return response()->json([
            'message' => 'Product variant created successfully.',
            'data' => new ProductVariantResource($variant),
        ], 201);
    }

    public function show(Product $product, ProductVariant $variant): JsonResponse
    {
        $this->productVariantService->ensureBelongsToProduct($product, $variant);

        $variant->load(['product:id,name,slug'])
            ->loadCount(['images', 'stockMovements']);

        return response()->json([
            'data' => new ProductVariantResource($variant),
        ]);
    }

    public function update(
        UpdateProductVariantRequest $request,
        Product $product,
        ProductVariant $variant
    ): JsonResponse {
        $variant = $this->productVariantService->update(
            $product,
            $variant,
            $request->validated()
        );

        return response()->json([
            'message' => 'Product variant updated successfully.',
            'data' => new ProductVariantResource($variant),
        ]);
    }

    public function destroy(Product $product, ProductVariant $variant): JsonResponse
    {
        $this->productVariantService->delete($product, $variant);

        return response()->json([
            'message' => 'Product variant deleted successfully.',
        ]);
    }
}
