<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use GuzzleHttp\Client;

$baseUrl = 'http://10.193.164.192:8000';
$client = new Client(['timeout' => 30]);
$jsonDir = __DIR__ . '/test_json/public_apis';
@mkdir($jsonDir, 0777, true);

echo "=== Test Public APIs (No Token Required) ===\n\n";

// 1. Test GET /categories (Public)
echo "1. Test GET /categories (Public)\n";
try {
    $r = $client->get("{$baseUrl}/api/categories", ['headers' => ['Accept' => 'application/json']]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Categories found: " . count($body['data']) . "\n";
    
    file_put_contents("$jsonDir/categories_public_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/categories',
        'headers' => ['Accept' => 'application/json'],
        'auth_required' => false
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/categories_public_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   ✅ Saved: categories_public_request.json, categories_public_response.json\n\n";
} catch(Exception $e) { echo "   ❌ FAIL: ".$e->getMessage()."\n\n"; }

// 2. Test GET /products (Public)
echo "2. Test GET /products (Public)\n";
try {
    $r = $client->get("{$baseUrl}/api/products", ['headers' => ['Accept' => 'application/json']]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Products found: " . $body['meta']['total'] . "\n";
    
    file_put_contents("$jsonDir/products_public_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/products',
        'headers' => ['Accept' => 'application/json'],
        'query_params' => [
            'search' => '(optional)',
            'status' => '(optional)',
            'category_id' => '(optional)',
            'gender' => '(optional)',
            'per_page' => '(optional, default: 15)'
        ],
        'auth_required' => false
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/products_public_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   ✅ Saved: products_public_request.json, products_public_response.json\n\n";
} catch(Exception $e) { echo "   ❌ FAIL: ".$e->getMessage()."\n\n"; }

// 3. Test GET /products/{id} (Public)
echo "3. Test GET /products/{id} (Public)\n";
try {
    $r = $client->get("{$baseUrl}/api/products/1", ['headers' => ['Accept' => 'application/json']]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Product found: " . ($body['data']['name'] ?? 'N/A') . "\n";
    
    file_put_contents("$jsonDir/product_show_public_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/products/{id}',
        'headers' => ['Accept' => 'application/json'],
        'auth_required' => false
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/product_show_public_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   ✅ Saved: product_show_public_request.json, product_show_public_response.json\n\n";
} catch(Exception $e) { echo "   ❌ FAIL: ".$e->getMessage()."\n\n"; }

// 4. Test GET /products/{id}/with-variants (Public)
echo "4. Test GET /products/{id}/with-variants (Public)\n";
try {
    $r = $client->get("{$baseUrl}/api/products/1/with-variants", ['headers' => ['Accept' => 'application/json']]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Product: " . ($body['data']['name'] ?? 'N/A') . "\n";
    echo "   Variants: " . count($body['data']['variants'] ?? []) . "\n";
    
    file_put_contents("$jsonDir/product_with_variants_public_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/products/{id}/with-variants',
        'headers' => ['Accept' => 'application/json'],
        'auth_required' => false
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/product_with_variants_public_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   ✅ Saved: product_with_variants_public_request.json, product_with_variants_public_response.json\n\n";
} catch(Exception $e) { echo "   ❌ FAIL: ".$e->getMessage()."\n\n"; }

// 5. Test GET /products.variants (Public)
echo "5. Test GET /products.variants (Public)\n";
try {
    $r = $client->get("{$baseUrl}/api/products.variants", ['headers' => ['Accept' => 'application/json']]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Variants found: " . $body['meta']['total'] . "\n";
    
    file_put_contents("$jsonDir/variants_public_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/products.variants',
        'headers' => ['Accept' => 'application/json'],
        'query_params' => [
            'search' => '(optional)',
            'product_id' => '(optional)',
            'per_page' => '(optional, default: 15)'
        ],
        'auth_required' => false
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/variants_public_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   ✅ Saved: variants_public_request.json, variants_public_response.json\n\n";
} catch(Exception $e) { echo "   ❌ FAIL: ".$e->getMessage()."\n\n"; }

// 6. Test GET /categories/{id} (Public)
echo "6. Test GET /categories/{id} (Public)\n";
try {
    $r = $client->get("{$baseUrl}/api/categories/1", ['headers' => ['Accept' => 'application/json']]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Category: " . ($body['data']['name'] ?? 'N/A') . "\n";
    
    file_put_contents("$jsonDir/category_show_public_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/categories/{id}',
        'headers' => ['Accept' => 'application/json'],
        'auth_required' => false
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/category_show_public_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   ✅ Saved: category_show_public_request.json, category_show_public_response.json\n\n";
} catch(Exception $e) { echo "   ❌ FAIL: ".$e->getMessage()."\n\n"; }

// 7. Test that POST /products still requires token
echo "7. Test POST /products (Should Require Token)\n";
try {
    $r = $client->post("{$baseUrl}/api/admin/products", [
        'headers' => ['Accept' => 'application/json'],
        'json' => ['name' => 'Test Product', 'category_id' => 1]
    ]);
    echo "   ❌ UNEXPECTED: Should have failed but got status {$r->getStatusCode()}\n";
} catch(Exception $e) {
    if ($e->getCode() === 401) {
        echo "   ✅ CORRECT: Requires authentication (401 Unauthorized)\n";
    } else {
        echo "   ❌ FAIL: ".$e->getMessage()."\n";
    }
}
echo "\n";

// 8. Test that POST /categories still requires token  
echo "8. Test POST /categories (Should Require Token)\n";
try {
    $r = $client->post("{$baseUrl}/api/admin/categories", [
        'headers' => ['Accept' => 'application/json'],
        'json' => ['name' => 'Test Category', 'slug' => 'test-category']
    ]);
    echo "   ❌ UNEXPECTED: Should have failed but got status {$r->getStatusCode()}\n";
} catch(Exception $e) {
    if ($e->getCode() === 401) {
        echo "   ✅ CORRECT: Requires authentication (401 Unauthorized)\n";
    } else {
        echo "   ❌ FAIL: ".$e->getMessage()."\n";
    }
}
echo "\n";

echo "=== Test Complete ===\n";
echo "Files saved to: {$jsonDir}\n\n";
echo "=== API Summary ===\n";
echo "PUBLIC (No Token Required):\n";
echo "  GET  /api/categories\n";
echo "  GET  /api/categories/{id}\n";
echo "  GET  /api/products\n";
echo "  GET  /api/products/{id}\n";
echo "  GET  /api/products/{id}/with-variants\n";
echo "  GET  /api/products.variants\n";
echo "  GET  /api/products.variants/{id}\n\n";
echo "ADMIN (Token Required):\n";
echo "  POST /api/admin/products\n";
echo "  PUT  /api/admin/products/{id}\n";
echo "  DELETE /api/admin/products/{id}\n";
echo "  POST /api/admin/categories\n";
echo "  PUT  /api/admin/categories/{id}\n";
echo "  DELETE /api/admin/categories/{id}\n";
echo "  POST /api/admin/products.variants\n";
echo "  PUT  /api/admin/products.variants/{id}\n";
echo "  DELETE /api/admin/products.variants/{id}\n\n";
