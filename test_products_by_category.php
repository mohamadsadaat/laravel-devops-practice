<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;
use GuzzleHttp\Client;

$baseUrl = 'http://10.193.164.192:8000';
$client = new Client(['timeout' => 30]);
$jsonDir = __DIR__ . '/test_json/products';
@mkdir($jsonDir, 0777, true);

echo "=== Products by Category API Test ===\n\n";

// 1. Login
echo "1. Login\n";
try {
    $r = $client->post("{$baseUrl}/api/login", ['json' => ['email'=>'saadat@kidsstore.com','password'=>'389235']]);
    $token = json_decode($r->getBody(), true)['token'] ?? '';
    echo "   OK\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; exit(1); }

$h = ['Authorization'=>'Bearer '.$token, 'Accept'=>'application/json'];

// 2. Get all categories first
echo "2. Get categories\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/categories", ['headers'=>$h]);
    $categories = json_decode($r->getBody(), true)['data'];
    echo "   Found " . count($categories) . " categories\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; exit(1); }

// 3. Test products without category filter (all products)
echo "3. Test GET /api/admin/products (All Products)\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/products", ['headers'=>$h]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Total products: " . $body['meta']['total'] . "\n";
    echo "   Current page: " . $body['meta']['current_page'] . "\n";
    
    file_put_contents("$jsonDir/all_products_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/admin/products',
        'headers' => ['Authorization' => 'Bearer {token}', 'Accept' => 'application/json'],
        'query_params' => [
            'search' => '(optional) - search in name, slug, brand',
            'status' => '(optional) - draft|active|archived',
            'category_id' => '(optional) - filter by category ID',
            'per_page' => '(optional, default: 15)'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/all_products_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   Saved: all_products_request.json, all_products_response.json\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }

// 4. Test products filtered by first category
if (!empty($categories)) {
    $firstCategory = $categories[0];
    echo "4. Test GET /api/admin/products?category_id={$firstCategory['id']}\n";
    echo "   Category: {$firstCategory['name']} (ID: {$firstCategory['id']})\n";
    
    try {
        $r = $client->get("{$baseUrl}/api/admin/products?category_id={$firstCategory['id']}", ['headers'=>$h]);
        $body = json_decode($r->getBody(), true);
        echo "   Status: {$r->getStatusCode()}\n";
        echo "   Products in this category: " . $body['meta']['total'] . "\n";
        
        file_put_contents("$jsonDir/products_by_category_request.json", json_encode([
            'method' => 'GET',
            'url' => '/api/admin/products',
            'headers' => ['Authorization' => 'Bearer {token}', 'Accept' => 'application/json'],
            'query_params' => [
                'category_id' => $firstCategory['id'],
                'search' => '(optional)',
                'status' => '(optional)',
                'per_page' => '(optional, default: 15)'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        file_put_contents("$jsonDir/products_by_category_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "   Saved: products_by_category_request.json, products_by_category_response.json\n\n";
    } catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }
    
    // 5. Test with search + category filter
    echo "5. Test GET /api/admin/products?category_id={$firstCategory['id']}&search=test\n";
    try {
        $r = $client->get("{$baseUrl}/api/admin/products?category_id={$firstCategory['id']}&search=test", ['headers'=>$h]);
        $body = json_decode($r->getBody(), true);
        echo "   Status: {$r->getStatusCode()}\n";
        echo "   Products matching 'test' in this category: " . $body['meta']['total'] . "\n";
        
        file_put_contents("$jsonDir/products_search_category_request.json", json_encode([
            'method' => 'GET',
            'url' => '/api/admin/products',
            'headers' => ['Authorization' => 'Bearer {token}', 'Accept' => 'application/json'],
            'query_params' => [
                'category_id' => $firstCategory['id'],
                'search' => 'test',
                'status' => '(optional)',
                'per_page' => '(optional, default: 15)'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        file_put_contents("$jsonDir/products_search_category_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "   Saved: products_search_category_request.json, products_search_category_response.json\n\n";
    } catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }
    
    // 6. Test with status + category filter
    echo "6. Test GET /api/admin/products?category_id={$firstCategory['id']}&status=active\n";
    try {
        $r = $client->get("{$baseUrl}/api/admin/products?category_id={$firstCategory['id']}&status=active", ['headers'=>$h]);
        $body = json_decode($r->getBody(), true);
        echo "   Status: {$r->getStatusCode()}\n";
        echo "   Active products in this category: " . $body['meta']['total'] . "\n";
        
        file_put_contents("$jsonDir/products_status_category_request.json", json_encode([
            'method' => 'GET',
            'url' => '/api/admin/products',
            'headers' => ['Authorization' => 'Bearer {token}', 'Accept' => 'application/json'],
            'query_params' => [
                'category_id' => $firstCategory['id'],
                'status' => 'active',
                'search' => '(optional)',
                'per_page' => '(optional, default: 15)'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        file_put_contents("$jsonDir/products_status_category_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "   Saved: products_status_category_request.json, products_status_category_response.json\n\n";
    } catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }
} else {
    echo "4. No categories found to test with\n\n";
}

// 7. Test with custom per_page
echo "7. Test GET /api/admin/products?per_page=5\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/products?per_page=5", ['headers'=>$h]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Products per page: " . count($body['data']) . "\n";
    echo "   Total pages: " . $body['meta']['last_page'] . "\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }

echo "=== All JSON files saved to: {$jsonDir} ===\n";
echo "\n=== API Summary ===\n";
echo "Endpoint: GET /api/admin/products\n";
echo "Auth: Required (Bearer Token)\n";
echo "Query Parameters:\n";
echo "  - category_id: Filter by category ID (optional)\n";
echo "  - search: Search in name, slug, brand (optional)\n";
echo "  - status: Filter by status (draft|active|archived) (optional)\n";
echo "  - per_page: Items per page (default: 15) (optional)\n";
echo "Response: Paginated product list with category, images, and variants count\n\n";
