<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Order;
use App\Models\OrderItem;

$baseUrl = 'http://10.193.164.192:8000';
$client = new GuzzleHttp\Client(['timeout' => 30]);
$jsonDir = __DIR__ . '/test_json/orders';
@mkdir($jsonDir, 0777, true);

echo "=== Orders API Test ===\n\n";

// 1. Login
echo "1. Login\n";
try {
    $r = $client->post("{$baseUrl}/api/login", ['json' => ['email'=>'saadat@kidsstore.com','password'=>'389235']]);
    $token = json_decode($r->getBody(), true)['token'] ?? '';
    echo "   OK\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; exit(1); }

$h = ['Authorization'=>'Bearer '.$token, 'Accept'=>'application/json'];

// 2. Seed test data: Category -> Product -> Variant -> Order -> OrderItem
echo "2. Seed test data\n";

$cat = Category::create(['name'=>'Test Category','slug'=>'test-cat-'.time(),'status'=>'active']);
echo "   Category: {$cat->id}\n";

$prod = Product::create([
    'category_id'=>$cat->id, 'name'=>'Test Product','slug'=>'test-prod-'.time(),
    'status'=>'active','base_price'=>500,'brand'=>'TestBrand'
]);
echo "   Product: {$prod->id}\n";

$v1 = ProductVariant::create([
    'product_id'=>$prod->id,'sku'=>'ORD-RED-'.time(),'color_name'=>'red',
    'size_name'=>'S','age_label'=>'3-6m','price'=>200,'compare_price'=>300,
    'quantity_on_hand'=>50,'quantity_reserved'=>0,'is_active'=>true
]);
$v2 = ProductVariant::create([
    'product_id'=>$prod->id,'sku'=>'ORD-BLU-'.time(),'color_name'=>'blue',
    'size_name'=>'M','age_label'=>'6-12m','price'=>250,'compare_price'=>400,
    'quantity_on_hand'=>30,'quantity_reserved'=>0,'is_active'=>true
]);
echo "   Variant1: {$v1->id}, Variant2: {$v2->id}\n";

$order1 = Order::create([
    'order_number'=>'ORD-'.strtoupper(uniqid()),'customer_name'=>'Ahmad Ali',
    'customer_phone'=>'0790123456','customer_address'=>'Baghdad, Al-Mansour',
    'city'=>'Baghdad','notes'=>'Please deliver before 5pm','status'=>'pending',
    'subtotal'=>650,'shipping_fee'=>25,'total'=>675,'placed_at'=>now()
]);
OrderItem::create([
    'order_id'=>$order1->id,'product_id'=>$prod->id,'variant_id'=>$v1->id,
    'product_name_snapshot'=>$prod->name,'variant_snapshot'=>'Red / S',
    'sku_snapshot'=>$v1->sku,'unit_price'=>200,'quantity'=>2,'line_total'=>400
]);
OrderItem::create([
    'order_id'=>$order1->id,'product_id'=>$prod->id,'variant_id'=>$v2->id,
    'product_name_snapshot'=>$prod->name,'variant_snapshot'=>'Blue / M',
    'sku_snapshot'=>$v2->sku,'unit_price'=>250,'quantity'=>1,'line_total'=>250
]);
echo "   Order1: {$order1->id} ({$order1->order_number})\n";

$order2 = Order::create([
    'order_number'=>'ORD-'.strtoupper(uniqid()),'customer_name'=>'Sara Hassan',
    'customer_phone'=>'0780123456','customer_address'=>'Erbil, 60m Street',
    'city'=>'Erbil','notes'=>null,'status'=>'confirmed',
    'subtotal'=>500,'shipping_fee'=>20,'total'=>520,'placed_at'=>now()
]);
OrderItem::create([
    'order_id'=>$order2->id,'product_id'=>$prod->id,'variant_id'=>$v1->id,
    'product_name_snapshot'=>$prod->name,'variant_snapshot'=>'Red / S',
    'sku_snapshot'=>$v1->sku,'unit_price'=>200,'quantity'=>1,'line_total'=>200
]);
OrderItem::create([
    'order_id'=>$order2->id,'product_id'=>$prod->id,'variant_id'=>$v2->id,
    'product_name_snapshot'=>$prod->name,'variant_snapshot'=>'Blue / M',
    'sku_snapshot'=>$v2->sku,'unit_price'=>250,'quantity'=>1,'line_total'=>250
]);
echo "   Order2: {$order2->id} ({$order2->order_number})\n\n";

// 3. Test GET /api/admin/orders (index)
echo "3. Test GET /api/admin/orders\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/orders", ['headers'=>$h]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Orders count: " . count($body['data']) . "\n";
    file_put_contents("$jsonDir/index_request.json", json_encode([
        'method'=>'GET','url'=>'/api/admin/orders',
        'headers'=>['Authorization'=>'Bearer {token}','Accept'=>'application/json'],
        'query_params'=>['search'=>'(optional)','status'=>'(optional: pending,confirmed,preparing,shipped,delivered,cancelled)','per_page'=>'(optional, default:15)']
    ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    file_put_contents("$jsonDir/index_response.json", json_encode($body, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    echo "   Saved: index_request.json, index_response.json\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }

// 4. Test GET /api/admin/orders/{order} (show)
echo "4. Test GET /api/admin/orders/{$order1->id}\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/orders/{$order1->id}", ['headers'=>$h]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    file_put_contents("$jsonDir/show_request.json", json_encode([
        'method'=>'GET','url'=>"/api/admin/orders/{order}",
        'headers'=>['Authorization'=>'Bearer {token}','Accept'=>'application/json'],
        'route_params'=>['order'=>'order ID']
    ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    file_put_contents("$jsonDir/show_response.json", json_encode($body, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    echo "   Saved: show_request.json, show_response.json\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }

// 5. Test PATCH /api/admin/orders/{order}/status (updateStatus)
echo "5. Test PATCH /api/admin/orders/{$order1->id}/status\n";
$statusData = ['status'=>'confirmed'];
try {
    $r = $client->patch("{$baseUrl}/api/admin/orders/{$order1->id}/status", [
        'headers'=>array_merge($h, ['Content-Type'=>'application/json']),
        'json'=>$statusData
    ]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   New order status: " . ($body['data']['status'] ?? 'unknown') . "\n";
    file_put_contents("$jsonDir/update_status_request.json", json_encode([
        'method'=>'PATCH','url'=>"/api/admin/orders/{order}/status",
        'headers'=>['Authorization'=>'Bearer {token}','Accept'=>'application/json','Content-Type'=>'application/json'],
        'route_params'=>['order'=>'order ID'],
        'body'=>['status'=>'pending | confirmed | preparing | shipped | delivered | cancelled']
    ], JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    file_put_contents("$jsonDir/update_status_response.json", json_encode($body, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    echo "   Saved: update_status_request.json, update_status_response.json\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }

// 6. Test update to shipped
echo "6. Test PATCH /api/admin/orders/{$order1->id}/status -> shipped\n";
try {
    $r = $client->patch("{$baseUrl}/api/admin/orders/{$order1->id}/status", [
        'headers'=>array_merge($h, ['Content-Type'=>'application/json']),
        'json'=>['status'=>'shipped']
    ]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   New order status: " . ($body['data']['status'] ?? 'unknown') . "\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }

// 7. Test filter by status
echo "7. Test GET /api/admin/orders?status=pending\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/orders?status=pending", ['headers'=>$h]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Pending orders: " . count($body['data']) . "\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }

echo "=== All JSON files saved to: {$jsonDir} ===\n";
