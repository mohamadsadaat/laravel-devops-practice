<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductVariant;
use GuzzleHttp\Client;

$baseUrl = 'http://10.193.164.192:8000';
$client = new Client(['timeout' => 30]);
$jsonDir = __DIR__ . '/test_json/checkout';
@mkdir($jsonDir, 0777, true);

echo "=== Checkout API Test ===\n\n";

// Find active variants with active products
$variants = ProductVariant::with('product:id,name,slug,status')
    ->where('is_active', true)
    ->whereHas('product', fn($q) => $q->where('status', 'active'))
    ->where('quantity_on_hand', '>', 0)
    ->limit(3)
    ->get();

if ($variants->count() < 2) {
    echo "Not enough active variants. Creating test data...\n";
    $cat = \App\Models\Category::first();
    $prod = \App\Models\Product::create([
        'category_id'=>$cat->id,'name'=>'Checkout Test Product','slug'=>'checkout-test-'.time(),
        'status'=>'active','base_price'=>300
    ]);
    $v1 = ProductVariant::create([
        'product_id'=>$prod->id,'sku'=>'CHK-RED-'.time(),'color_name'=>'red',
        'size_name'=>'L','age_label'=>'12-18m','price'=>300,'compare_price'=>400,
        'quantity_on_hand'=>20,'quantity_reserved'=>0,'is_active'=>true
    ]);
    $v2 = ProductVariant::create([
        'product_id'=>$prod->id,'sku'=>'CHK-GRN-'.time(),'color_name'=>'green',
        'size_name'=>'XL','age_label'=>'18-24m','price'=>350,'compare_price'=>450,
        'quantity_on_hand'=>15,'quantity_reserved'=>0,'is_active'=>true
    ]);
    $variants = collect([$v1, $v2]);
    echo "Created: Product {$prod->id}, Variant1 {$v1->id}, Variant2 {$v2->id}\n";
}

$v1 = $variants[0];
$v2 = $variants[1];

echo "Using variants: {$v1->id} ({$v1->sku}), {$v2->id} ({$v2->sku})\n\n";

// Build request body
$requestBody = [
    'customer_name' => 'Fatima Khalil',
    'customer_phone' => '07901112233',
    'customer_address' => 'Damascus, Abu Rummaneh, St. 15',
    'city' => 'Damascus',
    'notes' => 'Please call before delivery',
    'shipping_fee' => 30,
    'items' => [
        ['variant_id' => $v1->id, 'quantity' => 2],
        ['variant_id' => $v2->id, 'quantity' => 1],
    ]
];

// Save request JSON
file_put_contents("$jsonDir/request.json", json_encode([
    'method' => 'POST',
    'url' => '/api/checkout',
    'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json'
    ],
    'body' => $requestBody
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Request saved.\n\n";

// Send request (no auth needed - public route)
echo "Sending POST /api/checkout\n";
try {
    $r = $client->post("{$baseUrl}/api/checkout", [
        'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
        'json' => $requestBody
    ]);
    $body = json_decode($r->getBody(), true);
    echo "Status: {$r->getStatusCode()}\n";
    echo "Order ID: " . ($body['data']['id'] ?? 'N/A') . "\n";
    echo "Order Number: " . ($body['data']['order_number'] ?? 'N/A') . "\n";
    echo "Total: " . ($body['data']['total'] ?? 'N/A') . "\n";

    file_put_contents("$jsonDir/response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "Response saved.\n\n";
} catch (Exception $e) {
    $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
    echo "FAIL: $errorBody\n";
    file_put_contents("$jsonDir/response_error.json", $errorBody);
}

echo "=== Files saved to: {$jsonDir} ===\n";
