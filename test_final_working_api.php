<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use GuzzleHttp\Client;

$baseUrl = 'http://localhost:8002';
$client = new Client(['timeout' => 30]);
$jsonDir = __DIR__ . '/test_json/final_working_api';
@mkdir($jsonDir, 0777, true);

echo "=== Test Final Working Product Sales API ===\n\n";

// 1. Login
echo "1. Login\n";
try {
    $r = $client->post("{$baseUrl}/api/login", ['json' => ['email'=>'saadat@kidsstore.com','password'=>'389235']]);
    $token = json_decode($r->getBody(), true)['token'] ?? '';
    echo "   OK\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; exit(1); }

// 2. Test GET /api/products/sales
echo "2. Test GET /api/products/sales\n";
try {
    $r = $client->get("{$baseUrl}/api/products/sales", [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json'
        ]
    ]);
    
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Products: " . $body['summary']['total_products'] . "\n";
    echo "   Variants: " . $body['summary']['total_variants'] . "\n";
    echo "   Total Sold: " . $body['summary']['total_items_sold'] . "\n";
    echo "   Total Remaining: " . $body['summary']['total_items_remaining'] . "\n";
    
    // Save request and response
    file_put_contents("$jsonDir/working_api_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/products/sales',
        'headers' => [
            'Authorization' => 'Bearer {token}',
            'Accept' => 'application/json'
        ],
        'auth_required' => true
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/working_api_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   ✅ Saved: working_api_request.json, working_api_response.json\n\n";
    
    echo "   ✅ SUCCESS!\n\n";
    
    // Show detailed product information
    echo "   Product Sales Details:\n";
    foreach ($body['data'] as $product) {
        echo "     - منتج: {$product['name']}\n";
        echo "       * إجمالي المباع: {$product['total_sold']} قطعة\n";
        echo "       * إجمالي المتبقي: {$product['total_remaining']} قطعة\n";
        echo "       * عدد الفارينتس: {$product['variants_count']}\n";
        
        foreach ($product['variants'] as $variant) {
            echo "         - فارينت: {$variant['sku']} - {$variant['color_name']}/{$variant['size_name']}/{$variant['age_label']}\n";
            echo "           * المباع: {$variant['total_sold']} قطعة\n";
            echo "           * المتبقي: {$variant['available_quantity']} قطعة\n";
            echo "           * السعر: {$variant['price']}\n";
        }
        echo "\n";
    }
    
} catch(Exception $e) { 
    echo "   FAIL: ".$e->getMessage()."\n";
    if ($e->hasResponse()) {
        file_put_contents("$jsonDir/working_api_error.json", $e->getResponse()->getBody()->getContents());
        echo "   Error saved to: working_api_error.json\n";
    }
    echo "\n";
}

echo "=== Test Complete ===\n";
echo "Files saved to: {$jsonDir}\n\n";
