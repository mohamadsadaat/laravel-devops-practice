<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductVariant;
use App\Models\ProductImage;

echo "=== Adding Image to Variant 8 ===\n";

$variant = ProductVariant::find(8);
if ($variant) {
    echo "Variant found: {$variant->sku}\n";
    
    // Create image for variant 8
    $image = ProductImage::create([
        'product_id' => $variant->product_id,
        'variant_id' => $variant->id,
        'path' => 'product-images/variant8-pink.jpg',
        'alt_text' => 'Pink Variant Image',
        'sort_order' => 1,
        'is_primary' => true,
    ]);
    
    echo "Created image: {$image->id} for variant {$variant->id}\n";
    
    echo "\n=== Testing API ===\n";
    
    // Test the API
    $client = new GuzzleHttp\Client();
    
    $loginResponse = $client->post('http://10.118.118.192:8000/api/login', [
        'json' => [
            'email' => 'saadat@kidsstore.com',
            'password' => '389235'
        ]
    ]);
    
    $loginData = json_decode($loginResponse->getBody(), true);
    $token = $loginData['token'] ?? '';
    
    $productResponse = $client->get('http://10.118.118.192:8000/api/admin/products/13/with-variants', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]
    ]);
    
    echo "Status: " . $productResponse->getStatusCode() . "\n";
    echo "Response: " . $productResponse->getBody() . "\n";
    
} else {
    echo "Variant 8 not found\n";
}
