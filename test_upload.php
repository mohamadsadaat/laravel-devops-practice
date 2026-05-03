<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Test login
$user = App\Models\User::where('email', 'saadat@kidsstore.com')->first();
if (!$user) {
    echo "User not found!\n";
    exit;
}

$token = $user->createToken('test-token')->plainTextToken;
echo "Token: " . $token . "\n";

// Test API endpoint
$client = new GuzzleHttp\Client();

try {
    $response = $client->post('http://10.135.120.192:8000/api/admin/products/1/images', [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ],
        'multipart' => [
            [
                'name'     => 'image',
                'contents' => fopen('public/storage/categories/hCOKb9HVCFBJ95bphpd81T7rZ2jHIqnpNfOwm3tS.png', 'r'),
                'filename' => 'test.png'
            ],
            [
                'name'     => 'alt_text',
                'contents' => 'Test image'
            ],
            [
                'name'     => 'is_primary',
                'contents' => 'true'
            ]
        ]
    ]);

    echo "Response: " . $response->getBody() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
