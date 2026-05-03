<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductImageRequest;
use App\Http\Resources\Admin\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Catalog\ProductImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    public function __construct(
        private readonly ProductImageService $productImageService
    ) {
    }

    public function store(StoreProductImageRequest $request, Product $product): JsonResponse
    {
        $image = $this->productImageService->create($product, $request->validated());

        return response()->json([
            'message' => 'Product image uploaded successfully.',
            'data' => new ProductImageResource($image),
        ], 201);
    }

    public function destroy(Product $product, ProductImage $image): JsonResponse
    {
        $this->productImageService->delete($product, $image);

        return response()->json([
            'message' => 'Product image deleted successfully.',
        ]);
    }
}
