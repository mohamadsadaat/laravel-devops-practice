<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use GuzzleHttp\Client;

$baseUrl = 'http://10.193.164.192:8000';
$client = new Client(['timeout' => 30]);
$jsonDir = __DIR__ . '/test_json/order_status';
@mkdir($jsonDir, 0777, true);

echo "=== Test Order Status Update API ===\n\n";

// 1. Login
echo "1. Login\n";
try {
    $r = $client->post("{$baseUrl}/api/login", ['json' => ['email'=>'saadat@kidsstore.com','password'=>'389235']]);
    $token = json_decode($r->getBody(), true)['token'] ?? '';
    echo "   OK\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; exit(1); }

$h = ['Authorization'=>'Bearer '.$token, 'Accept' => 'application/json'];

// 2. Get all orders to find an order
echo "2. Get orders to find test order\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/orders", ['headers'=>$h]);
    $orders = json_decode($r->getBody(), true)['data'];
    echo "   Found " . count($orders) . " orders\n";
    
    if (empty($orders)) {
        echo "   No orders found. Creating test order first...\n";
        
        // Create a test order
        $r = $client->post("{$baseUrl}/api/checkout", [
            'headers' => ['Accept' => 'application/json'],
            'json' => [
                'customer_name' => 'Test Customer',
                'customer_phone' => '07901234567',
                'customer_address' => 'Test Address',
                'city' => 'Baghdad',
                'notes' => 'Test order for status update',
                'shipping_fee' => 10,
                'items' => [
                    ['variant_id' => 3, 'quantity' => 1] // Use existing variant
                ]
            ]
        ]);
        
        if ($r->getStatusCode() === 201) {
            $order = json_decode($r->getBody(), true)['data'];
            echo "   Created test order: {$order['order_number']} (ID: {$order['id']})\n";
            $orderId = $order['id'];
        } else {
            echo "   Failed to create test order\n";
            exit(1);
        }
    } else {
        $orderId = $orders[0]['id'];
        echo "   Using existing order ID: {$orderId}\n";
    }
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; exit(1); }

// 3. Get current order status
echo "\n3. Get current order status\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/orders/{$orderId}", ['headers'=>$h]);
    $order = json_decode($r->getBody(), true)['data'];
    echo "   Order: {$order['order_number']}\n";
    echo "   Current Status: {$order['status']}\n";
    echo "   Customer: {$order['customer_name']}\n";
    echo "   Total: {$order['total']}\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; }

// 4. Test PATCH /api/admin/orders/{order}/status - Update to confirmed
echo "\n4. Test PATCH /api/admin/orders/{$orderId}/status - Update to 'confirmed'\n";
try {
    $requestBody = [
        'method' => 'PATCH',
        'url' => "/api/admin/orders/{$orderId}/status",
        'headers' => [
            'Authorization' => 'Bearer {token}',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ],
        'body' => [
            'status' => 'confirmed'
        ]
    ];
    
    file_put_contents("$jsonDir/update_status_confirmed_request.json", json_encode($requestBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    $r = $client->patch("{$baseUrl}/api/admin/orders/{$orderId}/status", [
        'headers' => $h,
        'json' => ['status' => 'confirmed']
    ]);
    
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   New Status: " . ($body['data']['status'] ?? 'N/A') . "\n";
    
    file_put_contents("$jsonDir/update_status_confirmed_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   ✅ Saved: update_status_confirmed_request.json, update_status_confirmed_response.json\n\n";
} catch(Exception $e) { 
    echo "   FAIL: ".$e->getMessage()."\n";
    if ($e->hasResponse()) {
        file_put_contents("$jsonDir/update_status_confirmed_error.json", $e->getResponse()->getBody()->getContents());
        echo "   Error saved to: update_status_confirmed_error.json\n";
    }
}

// 5. Test PATCH - Update to preparing
echo "5. Test PATCH /api/admin/orders/{$orderId}/status - Update to 'preparing'\n";
try {
    $requestBody = [
        'method' => 'PATCH',
        'url' => "/api/admin/orders/{$orderId}/status",
        'headers' => [
            'Authorization' => 'Bearer {token}',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ],
        'body' => [
            'status' => 'preparing'
        ]
    ];
    
    file_put_contents("$jsonDir/update_status_preparing_request.json", json_encode($requestBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    $r = $client->patch("{$baseUrl}/api/admin/orders/{$orderId}/status", [
        'headers' => $h,
        'json' => ['status' => 'preparing']
    ]);
    
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   New Status: " . ($body['data']['status'] ?? 'N/A') . "\n";
    
    file_put_contents("$jsonDir/update_status_preparing_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   ✅ Saved: update_status_preparing_request.json, update_status_preparing_response.json\n\n";
} catch(Exception $e) { 
    echo "   FAIL: ".$e->getMessage()."\n";
    if ($e->hasResponse()) {
        file_put_contents("$jsonDir/update_status_preparing_error.json", $e->getResponse()->getBody()->getContents());
        echo "   Error saved to: update_status_preparing_error.json\n";
    }
}

// 6. Test PATCH - Update to shipped
echo "6. Test PATCH /api/admin/orders/{$orderId}/status - Update to 'shipped'\n";
try {
    $requestBody = [
        'method' => 'PATCH',
        'url' => "/api/admin/orders/{$orderId}/status",
        'headers' => [
            'Authorization' => 'Bearer {token}',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ],
        'body' => [
            'status' => 'shipped'
        ]
    ];
    
    file_put_contents("$jsonDir/update_status_shipped_request.json", json_encode($requestBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    $r = $client->patch("{$baseUrl}/api/admin/orders/{$orderId}/status", [
        'headers' => $h,
        'json' => ['status' => 'shipped']
    ]);
    
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   New Status: " . ($body['data']['status'] ?? 'N/A') . "\n";
    
    file_put_contents("$jsonDir/update_status_shipped_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   ✅ Saved: update_status_shipped_request.json, update_status_shipped_response.json\n\n";
} catch(Exception $e) { 
    echo "   FAIL: ".$e->getMessage()."\n";
    if ($e->hasResponse()) {
        file_put_contents("$jsonDir/update_status_shipped_error.json", $e->getResponse()->getBody()->getContents());
        echo "   Error saved to: update_status_shipped_error.json\n";
    }
}

// 7. Test PATCH - Update to delivered
echo "7. Test PATCH /api/admin/orders/{$orderId}/status - Update to 'delivered'\n";
try {
    $requestBody = [
        'method' => 'PATCH',
        'url' => "/api/admin/orders/{$orderId}/status",
        'headers' => [
            'Authorization' => 'Bearer {token}',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ],
        'body' => [
            'status' => 'delivered'
        ]
    ];
    
    file_put_contents("$jsonDir/update_status_delivered_request.json", json_encode($requestBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    $r = $client->patch("{$baseUrl}/api/admin/orders/{$orderId}/status", [
        'headers' => $h,
        'json' => ['status' => 'delivered']
    ]);
    
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   New Status: " . ($body['data']['status'] ?? 'N/A') . "\n";
    
    file_put_contents("$jsonDir/update_status_delivered_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   ✅ Saved: update_status_delivered_request.json, update_status_delivered_response.json\n\n";
} catch(Exception $e) { 
    echo "   FAIL: ".$e->getMessage()."\n";
    if ($e->hasResponse()) {
        file_put_contents("$jsonDir/update_status_delivered_error.json", $e->getResponse()->getBody()->getContents());
        echo "   Error saved to: update_status_delivered_error.json\n";
    }
}

// 8. Test PATCH - Update to cancelled
echo "8. Test PATCH /api/admin/orders/{$orderId}/status - Update to 'cancelled'\n";
try {
    $requestBody = [
        'method' => 'PATCH',
        'url' => "/api/admin/orders/{$orderId}/status",
        'headers' => [
            'Authorization' => 'Bearer {token}',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ],
        'body' => [
            'status' => 'cancelled'
        ]
    ];
    
    file_put_contents("$jsonDir/update_status_cancelled_request.json", json_encode($requestBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    $r = $client->patch("{$baseUrl}/api/admin/orders/{$orderId}/status", [
        'headers' => $h,
        'json' => ['status' => 'cancelled']
    ]);
    
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   New Status: " . ($body['data']['status'] ?? 'N/A') . "\n";
    
    file_put_contents("$jsonDir/update_status_cancelled_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   ✅ Saved: update_status_cancelled_request.json, update_status_cancelled_response.json\n\n";
} catch(Exception $e) { 
    echo "   FAIL: ".$e->getMessage()."\n";
    if ($e->hasResponse()) {
        file_put_contents("$jsonDir/update_status_cancelled_error.json", $e->getResponse()->getBody()->getContents());
        echo "   Error saved to: update_status_cancelled_error.json\n";
    }
}

// 9. Test invalid status
echo "9. Test PATCH - Update to invalid status 'invalid'\n";
try {
    $r = $client->patch("{$baseUrl}/api/admin/orders/{$orderId}/status", [
        'headers' => $h,
        'json' => ['status' => 'invalid']
    ]);
    
    echo "   ❌ UNEXPECTED: Should have failed but got status {$r->getStatusCode()}\n";
} catch(Exception $e) { 
    if ($e->getCode() === 422) {
        echo "   ✅ CORRECT: Invalid status rejected (422 Validation Error)\n";
    } else {
        echo "   ❌ FAIL: ".$e->getMessage()."\n";
    }
}

echo "\n=== Test Complete ===\n";
echo "Files saved to: {$jsonDir}\n\n";
echo "=== API Summary ===\n";
echo "Endpoint: PATCH /api/admin/orders/{order}/status\n";
echo "Auth: Required (Bearer Token)\n";
echo "Method: PATCH\n";
echo "Request Body:\n";
echo "  {\n";
echo "    \"status\": \"pending|confirmed|preparing|shipped|delivered|cancelled\"\n";
echo "  }\n";
echo "Valid Status Values:\n";
echo "  - pending (initial status)\n";
echo "  - confirmed (order confirmed)\n";
echo "  - preparing (order being prepared)\n";
echo "  - shipped (order shipped)\n";
echo "  - delivered (order delivered)\n";
echo "  - cancelled (order cancelled)\n";
echo "Response: Updated order with new status and timestamps\n\n";
