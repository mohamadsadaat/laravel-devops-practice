<?php

namespace App\Services\Catalog;

use App\Models\Product;
use Illuminate\Support\Str;

class ProductService
{
    public function paginate(
        ?string $search = null,
        ?string $status = null,
        ?int $categoryId = null,
        ?string $gender = null,
        int $perPage = 15
    ) {
        return Product::query()
            ->with(['category:id,name,slug'])
            ->with(['images' => function($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('sort_order', 'asc');
            }])
            ->withCount(['variants', 'images'])
            ->when($search, function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('brand', 'like', "%{$search}%");
                });
            })
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->when($categoryId, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($gender, fn ($query, $gender) => $query->where('gender', $gender))
            ->latest('id')
            ->paginate($perPage);
    }

    public function create(array $data): Product
    {
        if (isset($data['slug'])) {
            $data['slug'] = $this->makeUniqueSlug($data['slug']);
        } elseif (isset($data['name'])) {
            $data['slug'] = $this->makeUniqueSlug($data['name']);
        }

        $data['is_featured'] = $data['is_featured'] ?? false;

        return Product::create($data)
            ->load(['category:id,name,slug'])
            ->load(['images' => function($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('sort_order', 'asc');
            }])
            ->loadCount(['variants', 'images']);
    }

    public function update(Product $product, array $data): Product
    {
        if (array_key_exists('slug', $data) && filled($data['slug'])) {
            $data['slug'] = $this->makeUniqueSlug($data['slug'], $product->id);
        } elseif (array_key_exists('name', $data) && filled($data['name'])) {
            $data['slug'] = $this->makeUniqueSlug($data['name'], $product->id);
        }

        $product->update($data);

        return $product->refresh()
            ->load(['category:id,name,slug'])
            ->load(['images' => function($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('sort_order', 'asc');
            }])
            ->loadCount(['variants', 'images']);
    }

    public function delete(Product $product): void
    {
        // Check if product has any order items
        if ($product->orderItems()->exists()) {
            throw new \RuntimeException('Cannot delete product: it has associated orders. Consider archiving the product instead.');
        }

        // Check if product has variants
        if ($product->variants()->exists()) {
            throw new \RuntimeException('Cannot delete product: it has variants. Delete variants first or consider archiving the product instead.');
        }

        // Delete product images if they exist
        $product->images()->delete();

        $product->delete();
    }

    public function archive(Product $product): Product
    {
        $product->update(['status' => 'archived']);
        
        return $product->refresh()
            ->load(['category:id,name,slug'])
            ->load(['images' => function($query) {
                $query->orderBy('is_primary', 'desc')->orderBy('sort_order', 'asc');
            }])
            ->loadCount(['variants', 'images']);
    }

    private function makeUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        // Enhanced slug generation
        $baseSlug = $this->generateBaseSlug($value);
        $slug = $baseSlug !== '' ? $baseSlug : 'product-' . time();
        $counter = 1;

        while (
            Product::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function generateBaseSlug(string $value): string
    {
        // Remove special characters and normalize
        $slug = preg_replace('/[^a-zA-Z0-9\s-]/', '', $value);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        $slug = strtolower($slug);
        
        // Handle common abbreviations and improvements
        $replacements = [
            'usa' => 'united-states',
            'uk' => 'united-kingdom',
            'tshirt' => 't-shirt',
            'sweatshirt' => 'sweat-shirt',
            'raincoat' => 'rain-coat',
            'backpack' => 'back-pack',
        ];
        
        foreach ($replacements as $from => $to) {
            $slug = str_replace($from, $to, $slug);
        }
        
        // Limit length to 60 characters
        if (strlen($slug) > 60) {
            $slug = substr($slug, 0, 57) . '...';
        }
        
        return $slug;
    }
}