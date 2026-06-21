<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductImageService
{
    public function create(Product $product, array $data): ProductImage
    {
        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $data['path'] = $this->storeImage($data['image']);
        }
        
        unset($data['image']);
        unset($data['variant_id']);
        
        $data['product_id'] = $product->id;
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_primary'] = $data['is_primary'] ?? false;

        // If this is set as primary, unset other primary images
        if ($data['is_primary']) {
            ProductImage::where('product_id', $product->id)
                ->where('is_primary', true)
                ->update(['is_primary' => false]);
        }

        return ProductImage::create($data);
    }

    public function delete(Product $product, ProductImage $image): void
    {
        if ($image->product_id !== $product->id) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('Image not found for this product.');
        }

        if ($image->path) {
            Storage::disk('public')->delete($image->path);
        }

        $image->delete();
    }

    private function storeImage(UploadedFile $image): string
    {
        return $image->store('product-images', 'public');
    }
}
