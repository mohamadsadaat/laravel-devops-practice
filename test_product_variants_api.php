<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test login to get token
$client = new GuzzleHttp\Client();

try {
    $loginResponse = $client->post('http://10.118.118.192:8000/api/login', [
        'json' => [
            'email' => 'saadat@kidsstore.com',
            'password' => '389235'
        ]
    ]);

    $loginData = json_decode($loginResponse->getBody(), true);
    $token = $loginData['token'] ?? '';
    
    echo "✅ Login successful!\n";
    echo "Token: " . substr($token, 0, 20) . "...\n\n";
    
    // Test getting product with variants
    echo "🔍 Testing: GET /api/admin/products/7/with-variants\n";
    
    $productResponse = $client->get('http://10.118.118.192:8000/api/admin/products/7/with-variants', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ]
    ]);

    echo "Status: " . $productResponse->getStatusCode() . "\n";
    echo "Response: " . $productResponse->getBody() . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
