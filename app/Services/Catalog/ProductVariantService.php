<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductVariantService
{
    public function paginateAll(
        ?string $search = null,
        ?bool $isActive = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        return ProductVariant::query()
            ->with(['product:id,name,slug'])
            ->withCount(['images', 'stockMovements'])
            ->when(!is_null($isActive), function ($query) use ($isActive) {
                $query->where('is_active', $isActive);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('sku', 'like', "%{$search}%")
                        ->orWhere('color_name', 'like', "%{$search}%")
                        ->orWhere('size_name', 'like', "%{$search}%")
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
            ->with(['product:id,name,slug'])
            ->withCount(['images', 'stockMovements'])
            ->when($search, function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('sku', 'like', "%{$search}%")
                        ->orWhere('color_name', 'like', "%{$search}%")
                        ->orWhere('size_name', 'like', "%{$search}%")
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

        // Generate SKU if not provided
        if (!isset($data['sku']) || empty($data['sku'])) {
            $data['sku'] = $this->generateUniqueSku($product, $data);
        } else {
            // Ensure provided SKU is unique
            $data['sku'] = $this->makeUniqueSku($data['sku']);
        }

        return ProductVariant::create($data)
            ->load(['product:id,name,slug'])
            ->loadCount(['images', 'stockMovements']);
    }

    public function update(Product $product, ProductVariant $variant, array $data): ProductVariant
    {
        $this->ensureBelongsToProduct($product, $variant);

        $variant->update($data);

        return $variant->refresh()
            ->load(['product:id,name,slug'])
            ->loadCount(['images', 'stockMovements']);
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

    private function generateUniqueSku(Product $product, array $data): string
    {
        // Generate base SKU from product name, color, size, and age
        $baseSku = $this->generateBaseSku($product, $data);
        
        // Make it unique
        return $this->makeUniqueSku($baseSku);
    }

    private function generateBaseSku(Product $product, array $data): string
    {
        $parts = [];
        
        // Add product name part (first 3-5 characters)
        $productPart = $this->cleanString($product->name);
        $parts[] = substr($productPart, 0, 5);
        
        // Add color part (first 3-4 characters)
        if (!empty($data['color_name'])) {
            $colorPart = $this->cleanString($data['color_name']);
            $parts[] = substr($colorPart, 0, 4);
        }
        
        // Add size part
        if (!empty($data['size_name'])) {
            $sizePart = $this->cleanString($data['size_name']);
            $parts[] = substr($sizePart, 0, 3);
        }
        
        // Add age part
        if (!empty($data['age_label'])) {
            $agePart = $this->cleanString($data['age_label']);
            $parts[] = substr($agePart, 0, 3);
        }
        
        // Join parts with hyphens
        $sku = implode('-', $parts);
        
        // Limit length to 20 characters
        if (strlen($sku) > 20) {
            $sku = substr($sku, 0, 17) . '...';
        }
        
        return strtoupper($sku);
    }

    private function makeUniqueSku(string $baseSku): string
    {
        $sku = $baseSku;
        $counter = 1;

        while (ProductVariant::query()->where('sku', $sku)->exists()) {
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
