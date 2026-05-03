<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductVariant;
use App\Models\ProductImage;

echo "=== Checking Variant 8 Images ===\n";

$variant = ProductVariant::find(8);
if ($variant) {
    echo "Variant found: {$variant->sku}\n";
    echo "Product ID: {$variant->product_id}\n\n";
    
    echo "=== All Images in Database ===\n";
    $allImages = ProductImage::all();
    echo "Total images: " . $allImages->count() . "\n";
    
    foreach ($allImages as $image) {
        echo "Image ID: {$image->id}, Product ID: {$image->product_id}, Variant ID: " . ($image->variant_id ?? 'NULL') . ", Path: {$image->path}\n";
    }
    
    echo "\n=== Images for Variant 8 ===\n";
    $variantImages = $variant->images;
    echo "Images count: " . $variantImages->count() . "\n";
    
    foreach ($variantImages as $image) {
        echo "Image ID: {$image->id}, Path: {$image->path}, Is Primary: " . ($image->is_primary ? 'Yes' : 'No') . "\n";
    }
    
    echo "\n=== Images with variant_id = 8 ===\n";
    $directImages = ProductImage::where('variant_id', 8)->get();
    echo "Direct images count: " . $directImages->count() . "\n";
    
    foreach ($directImages as $image) {
        echo "Image ID: {$image->id}, Path: {$image->path}, Is Primary: " . ($image->is_primary ? 'Yes' : 'No') . "\n";
    }
    
} else {
    echo "Variant 8 not found\n";
}
