<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\CollectionResource;
use App\Http\Resources\Admin\CollectionWithProductsResource;
use App\Models\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CollectionController extends Controller
{
    public function index(Request $request)
    {
        $collections = Collection::query()
            ->where('is_active', true)
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', "%{$request->search}%"))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($request->integer('per_page', 15));

        return CollectionResource::collection($collections);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:collections,name',
            'slug' => 'nullable|string|max:255|unique:collections,slug',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10000',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        if (!isset($validated['slug']) || empty($validated['slug'])) {
            $validated['slug'] = $this->generateUniqueSlug($validated['name']);
        } else {
            $validated['slug'] = $this->makeUniqueSlug($validated['slug']);
        }

        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        // Handle file upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('collections', 'public');
            $validated['image_path'] = $imagePath;
        }

        unset($validated['image']);

        $collection = Collection::create($validated);

        return response()->json([
            'message' => 'Collection created successfully.',
            'data' => new CollectionResource($collection),
        ], 201);
    }

    public function show(Collection $collection): JsonResponse
    {
        abort_unless($collection->is_active, 404);

        $collection->loadCount([
            'products' => fn ($query) => $query
                ->where('status', 'active')
                ->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('is_active', true))
                ->whereHas('variants', fn ($variantQuery) => $variantQuery
                    ->where('is_active', true)
                    ->whereColumn('quantity_on_hand', '>', 'quantity_reserved')),
        ]);

        return response()->json([
            'data' => new CollectionResource($collection),
        ]);
    }

    public function showWithProducts(Collection $collection): JsonResponse
    {
        abort_unless($collection->is_active, 404);

        $collection->load([
            'products' => function ($query) {
                $query->select(['id', 'name', 'slug', 'category_id', 'brand', 'gender', 'base_price', 'status', 'is_featured'])
                    ->where('status', 'active')
                    ->whereHas('category', fn ($categoryQuery) => $categoryQuery->where('is_active', true))
                    ->whereHas('variants', fn ($variantQuery) => $variantQuery
                        ->where('is_active', true)
                        ->whereColumn('quantity_on_hand', '>', 'quantity_reserved'))
                    ->with(['category:id,name,slug'])
                    ->with(['images' => function ($imageQuery) {
                        $imageQuery->orderBy('is_primary', 'desc')->orderBy('sort_order', 'asc')->limit(3);
                    }])
                    ->withCount([
                        'variants as variants_count' => fn ($variantQuery) => $variantQuery
                            ->where('is_active', true)
                            ->whereColumn('quantity_on_hand', '>', 'quantity_reserved'),
                        'images',
                    ]);
            },
        ]);

        return response()->json([
            'data' => new CollectionWithProductsResource($collection),
        ]);
    }

    public function update(Request $request, Collection $collection): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255|unique:collections,name,' . $collection->id,
            'slug' => 'sometimes|string|max:255|unique:collections,slug,' . $collection->id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10000',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        if (isset($validated['slug']) && !empty($validated['slug'])) {
            $validated['slug'] = $this->makeUniqueSlug($validated['slug'], $collection->id);
        }

        // Handle file upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($collection->image_path && Storage::disk('public')->exists($collection->image_path)) {
                Storage::disk('public')->delete($collection->image_path);
            }
            $imagePath = $request->file('image')->store('collections', 'public');
            $validated['image_path'] = $imagePath;
        }

        unset($validated['image']);

        $collection->update($validated);

        return response()->json([
            'message' => 'Collection updated successfully.',
            'data' => new CollectionResource($collection),
        ]);
    }

    public function destroy(Collection $collection): JsonResponse
    {
        $collection->delete();

        return response()->json([
            'message' => 'Collection deleted successfully.',
        ]);
    }

    public function addProducts(Request $request, Collection $collection): JsonResponse
    {
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $collection->products()->syncWithoutDetaching($validated['product_ids']);

        return response()->json([
            'message' => 'Products added to collection successfully.',
            'data' => new CollectionResource($collection->load(['products', 'productsCount'])),
        ]);
    }

    public function removeProducts(Request $request, Collection $collection): JsonResponse
    {
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $collection->products()->detach($validated['product_ids']);

        return response()->json([
            'message' => 'Products removed from collection successfully.',
            'data' => new CollectionResource($collection->load(['products', 'productsCount'])),
        ]);
    }

    public function syncProducts(Request $request, Collection $collection): JsonResponse
    {
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id',
        ]);

        $collection->products()->sync($validated['product_ids']);

        return response()->json([
            'message' => 'Collection products synced successfully.',
            'data' => new CollectionResource($collection->load(['products', 'productsCount'])),
        ]);
    }

    private function generateUniqueSlug(string $value): string
    {
        $baseSlug = Str::slug($value);
        $slug = $baseSlug !== '' ? $baseSlug : 'collection-' . time();
        $counter = 1;

        while (Collection::query()->where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function makeUniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $slug = $value;
        $counter = 1;

        while (
            Collection::query()
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$value}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
