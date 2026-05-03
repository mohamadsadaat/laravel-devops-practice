<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inventory\AdjustInventoryRequest;
use App\Http\Requests\Admin\Inventory\SetInventoryRequest;
use App\Http\Resources\Admin\VariantInventoryResource;
use App\Http\Resources\Admin\StockMovementResource;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Services\Catalog\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventoryService
    ) {
    }

    public function adjust(
        AdjustInventoryRequest $request,
        ProductVariant $variant
    ): JsonResponse {
        $userId = auth()->id();
        $action = $request->validated()['action'];
        $quantity = $request->validated()['quantity'];
        $notes = $request->validated()['notes'] ?? null;

        $variant = match ($action) {
            'add' => $this->inventoryService->addStock(
                variant: $variant,
                quantity: $quantity,
                userId: $userId,
                notes: $notes,
            ),
            'remove' => $this->inventoryService->removeStock(
                variant: $variant,
                quantity: $quantity,
                userId: $userId,
                notes: $notes,
            ),
        };

        return response()->json([
            'message' => 'Inventory updated successfully.',
            'data' => new VariantInventoryResource($variant),
        ]);
    }

    public function set(
        SetInventoryRequest $request,
        ProductVariant $variant
    ): JsonResponse {
        $variant = $this->inventoryService->setOnHand(
            variant: $variant,
            newQuantity: $request->validated()['quantity_on_hand'],
            userId: auth()->id(),
            notes: $request->validated()['notes'] ?? null,
        );

        return response()->json([
            'message' => 'Inventory quantity set successfully.',
            'data' => new VariantInventoryResource($variant),
        ]);
    }

    public function movements(Request $request, ProductVariant $variant)
    {
        $movements = StockMovement::query()
            ->where('variant_id', $variant->id)
            ->latest('id')
            ->paginate($request->get('per_page', 20));

        return StockMovementResource::collection($movements);
    }
}
