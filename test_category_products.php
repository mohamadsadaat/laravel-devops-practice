<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use GuzzleHttp\Client;

$baseUrl = 'http://10.193.164.192:8000';
$client = new Client(['timeout' => 30]);
$jsonDir = __DIR__ . '/test_json/products';
@mkdir($jsonDir, 0777, true);

echo "=== Test Products by Category ID ===\n\n";

// 1. Login
echo "1. Login\n";
try {
    $r = $client->post("{$baseUrl}/api/login", ['json' => ['email'=>'saadat@kidsstore.com','password'=>'389235']]);
    $token = json_decode($r->getBody(), true)['token'] ?? '';
    echo "   OK\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; exit(1); }

$h = ['Authorization'=>'Bearer '.$token, 'Accept'=>'application/json'];

// 2. Test GET /api/admin/products?category_id=2
echo "2. Test GET /api/admin/products?category_id=2\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/products?category_id=2", ['headers'=>$h]);
    $body = json_decode($r->getBody(), true);
    
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Products in category 2: " . $body['meta']['total'] . "\n";
    echo "   Current page: " . $body['meta']['current_page'] . "\n";
    echo "   Per page: " . $body['meta']['per_page'] . "\n";
    
    // Save request
    file_put_contents("$jsonDir/category_2_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/admin/products',
        'headers' => [
            'Authorization' => 'Bearer {token}',
            'Accept' => 'application/json'
        ],
        'query_params' => [
            'category_id' => 2
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // Save response
    file_put_contents("$jsonDir/category_2_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    echo "   ✅ Saved: category_2_request.json, category_2_response.json\n\n";
    
    // Show product details
    if (!empty($body['data'])) {
        echo "   Products found:\n";
        foreach ($body['data'] as $index => $product) {
            echo "   ".($index+1).". {$product['name']} (ID: {$product['id']})\n";
            echo "      - Category: {$product['category']['name']}\n";
            echo "      - Status: {$product['status']}\n";
            echo "      - Price: {$product['base_price']}\n";
            echo "      - Variants: {$product['variants_count']}\n";
            echo "      - Images: {$product['images_count']}\n";
            if ($product['primary_image_url']) {
                echo "      - Primary Image: {$product['primary_image_url']}\n";
            }
            echo "\n";
        }
    } else {
        echo "   ⚠️  No products found in category 2\n\n";
    }
    
} catch(Exception $e) { 
    echo "   ❌ FAIL: ".$e->getMessage()."\n";
    if ($e->hasResponse()) {
        file_put_contents("$jsonDir/category_2_error.json", $e->getResponse()->getBody()->getContents());
        echo "   Error saved to: category_2_error.json\n";
    }
    echo "\n";
}

// 3. Test with different per_page
echo "3. Test GET /api/admin/products?category_id=2&per_page=1\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/products?category_id=2&per_page=1", ['headers'=>$h]);
    $body = json_decode($r->getBody(), true);
    
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Products per page: " . count($body['data']) . "\n";
    echo "   Total pages: " . $body['meta']['last_page'] . "\n";
    
    file_put_contents("$jsonDir/category_2_per_page_1_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/admin/products',
        'headers' => ['Authorization' => 'Bearer {token}', 'Accept' => 'application/json'],
        'query_params' => [
            'category_id' => 2,
            'per_page' => 1
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/category_2_per_page_1_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   ✅ Saved: category_2_per_page_1_request.json, category_2_per_page_1_response.json\n\n";
    
} catch(Exception $e) { echo "   ❌ FAIL: ".$e->getMessage()."\n\n"; }

echo "=== Test Complete ===\n";
echo "Files saved to: {$jsonDir}\n";
