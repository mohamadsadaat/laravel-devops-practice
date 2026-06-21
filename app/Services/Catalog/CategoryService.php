<?php

namespace App\Services\Catalog;

use App\Models\Category;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryService
{
    public function paginate(?string $search = null, int $perPage = 15)
    {
        return Category::query()
            ->where('is_active', true)
            ->withCount([
                'products' => fn ($query) => $query
                    ->where('status', 'active')
                    ->whereHas('variants', fn ($variantQuery) => $variantQuery
                        ->where('is_active', true)
                        ->whereColumn('quantity_on_hand', '>', 'quantity_reserved')),
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function create(array $data): Category
    {
        if (isset($data['slug'])) {
            $data['slug'] = $this->makeUniqueSlug($data['slug']);
        } elseif (isset($data['name'])) {
            $data['slug'] = $this->makeUniqueSlug($data['name']);
        }

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['image_path'] = $this->storeImage($data['image']);
        }

        unset($data['image']);

        $data['is_active'] = $data['is_active'] ?? true;
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return Category::create($data);
    }

    public function update(Category $category, array $data): Category
    {
        if (array_key_exists('slug', $data) && filled($data['slug'])) {
            $data['slug'] = $this->makeUniqueSlug($data['slug'], $category->id);
        } elseif (array_key_exists('name', $data) && filled($data['name'])) {
            $data['slug'] = $this->makeUniqueSlug($data['name'], $category->id);
        }

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            if ($category->image_path) {
                Storage::disk('public')->delete($category->image_path);
            }

            $data['image_path'] = $this->storeImage($data['image']);
        }

        unset($data['image']);

        $category->update($data);

        return $category->refresh()->loadCount('products');
    }

    public function delete(Category $category): void
    {
        if ($category->image_path) {
            Storage::disk('public')->delete($category->image_path);
        }

        $category->delete();
    }

    private function storeImage(UploadedFile $image): string
    {
        return $image->store('categories', 'public');
    }

    private function makeUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($value);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Category::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
