<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use GuzzleHttp\Client;

$baseUrl = 'http://10.193.164.192:8000';
$client = new Client(['timeout' => 30]);
$jsonDir = __DIR__ . '/test_json/products';
@mkdir($jsonDir, 0777, true);

echo "=== Test Products by Gender API ===\n\n";

// 1. Login
echo "1. Login\n";
try {
    $r = $client->post("{$baseUrl}/api/login", ['json' => ['email'=>'saadat@kidsstore.com','password'=>'389235']]);
    $token = json_decode($r->getBody(), true)['token'] ?? '';
    echo "   OK\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; exit(1); }

$h = ['Authorization'=>'Bearer '.$token, 'Accept'=>'application/json'];

// 2. Test all products (no gender filter)
echo "2. Test GET /api/admin/products (All Products)\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/products", ['headers'=>$h]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Total products: " . $body['meta']['total'] . "\n";
    
    file_put_contents("$jsonDir/all_products_gender_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/admin/products',
        'headers' => ['Authorization' => 'Bearer {token}', 'Accept' => 'application/json'],
        'query_params' => [
            'search' => '(optional)',
            'status' => '(optional)',
            'category_id' => '(optional)',
            'gender' => '(optional)',
            'per_page' => '(optional, default: 15)'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/all_products_gender_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   Saved: all_products_gender_request.json, all_products_gender_response.json\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }

// 3. Test products with gender = 'boy'
echo "3. Test GET /api/admin/products?gender=boy\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/products?gender=boy", ['headers'=>$h]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Boy products: " . $body['meta']['total'] . "\n";
    
    file_put_contents("$jsonDir/products_boy_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/admin/products',
        'headers' => ['Authorization' => 'Bearer {token}', 'Accept' => 'application/json'],
        'query_params' => [
            'gender' => 'boy',
            'search' => '(optional)',
            'status' => '(optional)',
            'category_id' => '(optional)',
            'per_page' => '(optional, default: 15)'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/products_boy_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   Saved: products_boy_request.json, products_boy_response.json\n";
    
    if (!empty($body['data'])) {
        echo "   Boy products found:\n";
        foreach ($body['data'] as $index => $product) {
            $gender = $product['gender'] ?? 'NULL';
            echo "   ".($index+1).". {$product['name']} (Gender: {$gender})\n";
        }
    }
    echo "\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }

// 4. Test products with gender = 'girl'
echo "4. Test GET /api/admin/products?gender=girl\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/products?gender=girl", ['headers'=>$h]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Girl products: " . $body['meta']['total'] . "\n";
    
    file_put_contents("$jsonDir/products_girl_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/admin/products',
        'headers' => ['Authorization' => 'Bearer {token}', 'Accept' => 'application/json'],
        'query_params' => [
            'gender' => 'girl',
            'search' => '(optional)',
            'status' => '(optional)',
            'category_id' => '(optional)',
            'per_page' => '(optional, default: 15)'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/products_girl_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   Saved: products_girl_request.json, products_girl_response.json\n";
    
    if (!empty($body['data'])) {
        echo "   Female products found:\n";
        foreach ($body['data'] as $index => $product) {
            $gender = $product['gender'] ?? 'NULL';
            echo "   ".($index+1).". {$product['name']} (Gender: {$gender})\n";
        }
    }
    echo "\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }

// 5. Test products with gender = 'unisex'
echo "5. Test GET /api/admin/products?gender=unisex\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/products?gender=unisex", ['headers'=>$h]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Unisex products: " . $body['meta']['total'] . "\n";
    
    file_put_contents("$jsonDir/products_unisex_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/admin/products',
        'headers' => ['Authorization' => 'Bearer {token}', 'Accept' => 'application/json'],
        'query_params' => [
            'gender' => 'unisex',
            'search' => '(optional)',
            'status' => '(optional)',
            'category_id' => '(optional)',
            'per_page' => '(optional, default: 15)'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/products_unisex_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   Saved: products_unisex_request.json, products_unisex_response.json\n";
    
    if (!empty($body['data'])) {
        echo "   Unisex products found:\n";
        foreach ($body['data'] as $index => $product) {
            $gender = $product['gender'] ?? 'NULL';
            echo "   ".($index+1).". {$product['name']} (Gender: {$gender})\n";
        }
    }
    echo "\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }

// 6. Test products with gender filter + category filter
echo "6. Test GET /api/admin/products?gender=boy&category_id=2\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/products?gender=boy&category_id=2", ['headers'=>$h]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Boy products in category 2: " . $body['meta']['total'] . "\n";
    
    file_put_contents("$jsonDir/products_boy_category_2_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/admin/products',
        'headers' => ['Authorization' => 'Bearer {token}', 'Accept' => 'application/json'],
        'query_params' => [
            'gender' => 'boy',
            'category_id' => 2,
            'search' => '(optional)',
            'status' => '(optional)',
            'per_page' => '(optional, default: 15)'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/products_boy_category_2_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   Saved: products_boy_category_2_request.json, products_boy_category_2_response.json\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }

// 7. Test products with gender filter + search
echo "7. Test GET /api/admin/products?gender=girl&search=p\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/products?gender=girl&search=p", ['headers'=>$h]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Girl products matching 'p': " . $body['meta']['total'] . "\n";
    
    file_put_contents("$jsonDir/products_girl_search_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/admin/products',
        'headers' => ['Authorization' => 'Bearer {token}', 'Accept' => 'application/json'],
        'query_params' => [
            'gender' => 'girl',
            'search' => 'p',
            'status' => '(optional)',
            'category_id' => '(optional)',
            'per_page' => '(optional, default: 15)'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/products_girl_search_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   Saved: products_girl_search_request.json, products_girl_search_response.json\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }

echo "=== Test Complete ===\n";
echo "Files saved to: {$jsonDir}\n\n";
echo "=== API Summary ===\n";
echo "Endpoint: GET /api/admin/products\n";
echo "Auth: Required (Bearer Token)\n";
echo "Query Parameters:\n";
echo "  - gender: Filter by gender (boy|girl|unisex) (optional)\n";
echo "  - category_id: Filter by category ID (optional)\n";
echo "  - search: Search in name, slug, brand (optional)\n";
echo "  - status: Filter by status (draft|active|archived) (optional)\n";
echo "  - per_page: Items per page (default: 15) (optional)\n";
echo "Response: Paginated product list with all filters applied\n\n";
