<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductVariantService
{
    public function paginateAll(
        ?string $search = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return ProductVariant::query()
            ->with(['product:id,name,slug,base_price,status,category_id'])
            ->withCount(['stockMovements'])
            ->where('is_active', true)
            ->whereColumn('quantity_on_hand', '>', 'quantity_reserved')
            ->whereHas('product', function ($query) {
                $query->where('status', 'active')
                    ->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('is_active', true));
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('sku', 'like', "%{$search}%")
                        ->orWhere('age_label', 'like', "%{$search}%")
                        ->orWhereHas('product', function ($productQuery) use ($search) {
                            $productQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->latest('id')
            ->paginate($perPage);
    }

    public function paginateByProduct(
        Product $product,
        ?string $search = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('is_active', true)
            ->whereColumn('quantity_on_hand', '>', 'quantity_reserved')
            ->whereHas('product', function ($query) {
                $query->where('status', 'active')
                    ->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('is_active', true));
            })
            ->with(['product:id,name,slug,base_price,status,category_id'])
            ->withCount(['stockMovements'])
            ->when($search, function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('sku', 'like', "%{$search}%")
                        ->orWhere('age_label', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate($perPage);
    }

    public function create(Product $product, array $data): ProductVariant
    {
        $data['product_id'] = $product->id;
        $data['quantity_on_hand'] = $data['quantity_on_hand'] ?? 0;
        $data['quantity_reserved'] = $data['quantity_reserved'] ?? 0;
        $data['is_active'] = $data['is_active'] ?? true;

        $data['sku'] = $this->generateUniqueSku($product, $data);

        return ProductVariant::create($data)
            ->load(['product:id,name,slug,base_price,status,category_id'])
            ->loadCount(['stockMovements']);
    }

    public function update(Product $product, ProductVariant $variant, array $data): ProductVariant
    {
        $this->ensureBelongsToProduct($product, $variant);

        if (array_key_exists('age_label', $data) && $data['age_label'] !== $variant->age_label) {
            $data['sku'] = $this->generateUniqueSku($product, $data, $variant->id);
        }

        $variant->update($data);

        return $variant->refresh()
            ->load(['product:id,name,slug,base_price,status,category_id'])
            ->loadCount(['stockMovements']);
    }

    public function delete(Product $product, ProductVariant $variant): void
    {
        $this->ensureBelongsToProduct($product, $variant);

        $variant->delete();
    }

    public function ensureBelongsToProduct(Product $product, ProductVariant $variant): void
    {
        if ($variant->product_id !== $product->id) {
            throw new NotFoundHttpException('Variant not found for this product.');
        }
    }

    private function generateUniqueSku(Product $product, array $data, ?int $ignoreId = null): string
    {
        $baseSku = $this->generateBaseSku($product, $data);
        
        return $this->makeUniqueSku($baseSku, $ignoreId);
    }

    private function generateBaseSku(Product $product, array $data): string
    {
        $parts = [];
        
        $productPart = $this->cleanString($product->name);
        $parts[] = $productPart !== '' ? substr($productPart, 0, 5) : 'p' . $product->id;

        if (!empty($data['age_label'])) {
            $agePart = $this->cleanString($data['age_label']);
            $parts[] = $agePart !== ''
                ? substr($agePart, 0, 8)
                : 'a' . substr((string) abs(crc32($data['age_label'])), 0, 6);
        }
        
        $sku = implode('-', $parts);
        
        if (strlen($sku) > 20) {
            $sku = substr($sku, 0, 17) . '...';
        }
        
        return strtoupper($sku);
    }

    private function makeUniqueSku(string $baseSku, ?int $ignoreId = null): string
    {
        $sku = $baseSku;
        $counter = 1;

        while (
            ProductVariant::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('sku', $sku)
                ->exists()
        ) {
            $sku = $baseSku . '-' . $counter;
            $counter++;
        }

        return $sku;
    }

    private function cleanString(string $value): string
    {
        // Remove special characters and normalize
        $clean = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $value);
        $clean = preg_replace('/[\s\-]+/', '-', $clean);
        $clean = trim($clean, '-');
        return strtolower($clean);
    }
}
