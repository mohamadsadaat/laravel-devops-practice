<?php

namespace App\Services\Catalog;

use App\Models\ProductVariant;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public function addStock(
        ProductVariant $variant,
        int $quantity,
        ?int $userId = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): ProductVariant {
        return DB::transaction(function () use (
            $variant,
            $quantity,
            $userId,
            $notes,
            $referenceType,
            $referenceId
        ) {
            $lockedVariant = $this->lockVariant($variant->id);

            $this->ensurePositiveQuantity($quantity);

            $lockedVariant->quantity_on_hand += $quantity;
            $lockedVariant->save();

            $this->createMovement(
                variantId: $lockedVariant->id,
                type: 'manual_add',
                quantity: $quantity,
                userId: $userId,
                notes: $notes,
                referenceType: $referenceType,
                referenceId: $referenceId,
            );

            return $lockedVariant->refresh();
        }, 5);
    }

    public function removeStock(
        ProductVariant $variant,
        int $quantity,
        ?int $userId = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): ProductVariant {
        return DB::transaction(function () use (
            $variant,
            $quantity,
            $userId,
            $notes,
            $referenceType,
            $referenceId
        ) {
            $lockedVariant = $this->lockVariant($variant->id);

            $this->ensurePositiveQuantity($quantity);

            if ($lockedVariant->quantity_on_hand < $quantity) {
                throw new RuntimeException('Not enough stock to remove this quantity.');
            }

            if (($lockedVariant->quantity_on_hand - $quantity) < $lockedVariant->quantity_reserved) {
                throw new RuntimeException('Cannot reduce stock below reserved quantity.');
            }

            $lockedVariant->quantity_on_hand -= $quantity;
            $lockedVariant->save();

            $this->createMovement(
                variantId: $lockedVariant->id,
                type: 'manual_remove',
                quantity: $quantity,
                userId: $userId,
                notes: $notes,
                referenceType: $referenceType,
                referenceId: $referenceId,
            );

            return $lockedVariant->refresh();
        }, 5);
    }

    public function setOnHand(
        ProductVariant $variant,
        int $newQuantity,
        ?int $userId = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): ProductVariant {
        return DB::transaction(function () use (
            $variant,
            $newQuantity,
            $userId,
            $notes,
            $referenceType,
            $referenceId
        ) {
            $lockedVariant = $this->lockVariant($variant->id);

            if ($newQuantity < 0) {
                throw new RuntimeException('Quantity cannot be negative.');
            }

            if ($newQuantity < $lockedVariant->quantity_reserved) {
                throw new RuntimeException('On-hand quantity cannot be less than reserved quantity.');
            }

            $oldQuantity = (int) $lockedVariant->quantity_on_hand;
            $difference = abs($newQuantity - $oldQuantity);

            $lockedVariant->quantity_on_hand = $newQuantity;
            $lockedVariant->save();

            $this->createMovement(
                variantId: $lockedVariant->id,
                type: 'adjustment',
                quantity: $difference,
                userId: $userId,
                notes: $notes ?: "Set on-hand quantity from {$oldQuantity} to {$newQuantity}",
                referenceType: $referenceType,
                referenceId: $referenceId,
            );

            return $lockedVariant->refresh();
        }, 5);
    }

    public function sellStock(
        ProductVariant $variant,
        int $quantity,
        ?int $userId = null,
        ?string $notes = null,
        ?string $referenceType = 'order',
        ?int $referenceId = null
    ): ProductVariant {
        return DB::transaction(function () use (
            $variant,
            $quantity,
            $userId,
            $notes,
            $referenceType,
            $referenceId
        ) {
            $lockedVariant = $this->lockVariant($variant->id);

            $this->ensurePositiveQuantity($quantity);

            $available = (int) $lockedVariant->quantity_on_hand - (int) $lockedVariant->quantity_reserved;

            if ($available < $quantity) {
                throw new RuntimeException('Insufficient available stock for sale.');
            }

            $lockedVariant->quantity_on_hand -= $quantity;
            $lockedVariant->save();

            $this->createMovement(
                variantId: $lockedVariant->id,
                type: 'sale',
                quantity: $quantity,
                userId: $userId,
                notes: $notes,
                referenceType: $referenceType,
                referenceId: $referenceId,
            );

            return $lockedVariant->refresh();
        }, 5);
    }

    public function returnCancelledStock(
        ProductVariant $variant,
        int $quantity,
        ?int $userId = null,
        ?string $notes = null,
        ?string $referenceType = 'order',
        ?int $referenceId = null
    ): ProductVariant {
        return DB::transaction(function () use (
            $variant,
            $quantity,
            $userId,
            $notes,
            $referenceType,
            $referenceId
        ) {
            $lockedVariant = $this->lockVariant($variant->id);

            $this->ensurePositiveQuantity($quantity);

            $lockedVariant->quantity_on_hand += $quantity;
            $lockedVariant->save();

            $this->createMovement(
                variantId: $lockedVariant->id,
                type: 'cancel_return',
                quantity: $quantity,
                userId: $userId,
                notes: $notes,
                referenceType: $referenceType,
                referenceId: $referenceId,
            );

            return $lockedVariant->refresh();
        }, 5);
    }

    private function lockVariant(int $variantId): ProductVariant
    {
        return ProductVariant::query()
            ->whereKey($variantId)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensurePositiveQuantity(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new RuntimeException('Quantity must be greater than zero.');
        }
    }

    private function createMovement(
        int $variantId,
        string $type,
        int $quantity,
        ?int $userId = null,
        ?string $notes = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): StockMovement {
        return StockMovement::query()->create([
            'variant_id' => $variantId,
            'type' => $type,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => $userId,
        ]);
    }
}