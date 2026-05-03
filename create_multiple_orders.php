<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Customer;
use GuzzleHttp\Client;

$baseUrl = 'http://10.193.164.192:8000';
$client = new Client(['timeout' => 30]);
$jsonDir = __DIR__ . '/test_json/multiple_orders';
@mkdir($jsonDir, 0777, true);

echo "=== Create Multiple Orders with Multiple Products ===\n\n";

// 1. Login
echo "1. Login\n";
try {
    $r = $client->post("{$baseUrl}/api/login", ['json' => ['email'=>'saadat@kidsstore.com','password'=>'389235']]);
    $token = json_decode($r->getBody(), true)['token'] ?? '';
    echo "   OK\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; exit(1); }

$h = ['Authorization'=>'Bearer '.$token, 'Accept' => 'application/json'];

// 2. Get available products and variants
echo "2. Get available products and variants\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/products", ['headers'=>$h]);
    $products = json_decode($r->getBody(), true)['data'];
    
    if (empty($products)) {
        echo "   No products found. Creating test products first...\n";
        
        // Create test products with variants
        $category = Category::first();
        if (!$category) {
            $category = Category::create([
                'name' => 'Test Category',
                'slug' => 'test-category-' . time(),
                'description' => 'Test category for multiple orders'
            ]);
        }
        
        $testProducts = [
            [
                'name' => 'Blue T-Shirt',
                'slug' => 'blue-tshirt-' . time(),
                'description' => 'Comfortable blue t-shirt',
                'category_id' => $category->id,
                'base_price' => 25.99,
                'brand' => 'KidsBrand',
                'gender' => 'boy',
                'status' => 'active'
            ],
            [
                'name' => 'Pink Dress',
                'slug' => 'pink-dress-' . time(),
                'description' => 'Beautiful pink dress',
                'category_id' => $category->id,
                'base_price' => 35.99,
                'brand' => 'KidsBrand',
                'gender' => 'girl',
                'status' => 'active'
            ],
            [
                'name' => 'Yellow Hoodie',
                'slug' => 'yellow-hoodie-' . time(),
                'description' => 'Warm yellow hoodie',
                'category_id' => $category->id,
                'base_price' => 45.99,
                'brand' => 'KidsBrand',
                'gender' => 'unisex',
                'status' => 'active'
            ]
        ];
        
        foreach ($testProducts as $productData) {
            $product = Product::create($productData);
            
            // Create variants for each product
            $variants = [
                ['sku' => strtoupper(substr($product->slug, 0, 3)) . '-S', 'color_name' => 'Blue', 'size_name' => 'S', 'age_label' => '2-4 years', 'price' => $product->base_price],
                ['sku' => strtoupper(substr($product->slug, 0, 3)) . '-M', 'color_name' => 'Blue', 'size_name' => 'M', 'age_label' => '4-6 years', 'price' => $product->base_price + 5],
                ['sku' => strtoupper(substr($product->slug, 0, 3)) . '-L', 'color_name' => 'Blue', 'size_name' => 'L', 'age_label' => '6-8 years', 'price' => $product->base_price + 10],
            ];
            
            foreach ($variants as $variantData) {
                $variant = $product->variants()->create($variantData);
                // Add stock
                $variant->inventory()->create([
                    'quantity_on_hand' => 50,
                    'quantity_reserved' => 0,
                    'reorder_level' => 10,
                    'max_stock' => 100
                ]);
            }
        }
        
        // Get products again
        $r = $client->get("{$baseUrl}/api/admin/products", ['headers'=>$h]);
        $products = json_decode($r->getBody(), true)['data'];
    }
    
    echo "   Found " . count($products) . " products\n";
    
    // Get variants for each product
    $availableVariants = [];
    foreach ($products as $product) {
        $r = $client->get("{$baseUrl}/api/admin/products/{$product['id']}/with-variants", ['headers'=>$h]);
        $response = json_decode($r->getBody(), true);
        $productWithVariants = $response['data'] ?? $response;
        
        if (!empty($productWithVariants['variants'])) {
            foreach ($productWithVariants['variants'] as $variant) {
                $availableVariants[] = [
                    'id' => $variant['id'],
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'sku' => $variant['sku'],
                    'color' => $variant['color_name'],
                    'size' => $variant['size_name'],
                    'age' => $variant['age_label'],
                    'price' => $variant['price'],
                    'stock' => $variant['quantity_on_hand'] ?? 0
                ];
            }
        }
    }
    
    echo "   Available variants: " . count($availableVariants) . "\n";
    
    // If no variants found, create some test variants
    if (empty($availableVariants)) {
        echo "   No variants found. Creating test variants...\n";
        
        foreach ($products as $product) {
            // Create variants for each product
            $colorNames = ['Red', 'Green', 'Blue', 'Yellow', 'Purple', 'Orange'];
            $colorName = $colorNames[($product['id'] - 1) % count($colorNames)];
            
            $variants = [
                ['sku' => strtoupper(substr($product['slug'], 0, 3)) . '-S-' . time() . rand(100, 999), 'color_name' => $colorName, 'size_name' => 'S', 'age_label' => '2-4 years', 'price' => $product['base_price']],
                ['sku' => strtoupper(substr($product['slug'], 0, 3)) . '-M-' . time() . rand(100, 999), 'color_name' => $colorName, 'size_name' => 'M', 'age_label' => '4-6 years', 'price' => $product['base_price'] + 5],
            ];
            
            foreach ($variants as $variantData) {
                $productModel = Product::find($product['id']);
                $variant = $productModel->variants()->create($variantData);
                
                // Add stock directly to variant
                $variant->update([
                    'quantity_on_hand' => 50,
                    'quantity_reserved' => 0,
                    'is_active' => true
                ]);
                
                $availableVariants[] = [
                    'id' => $variant->id,
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'sku' => $variant->sku,
                    'color' => $variant->color_name,
                    'size' => $variant->size_name,
                    'age' => $variant->age_label,
                    'price' => $variant->price,
                    'stock' => 50
                ];
            }
        }
        
        echo "   Created " . count($availableVariants) . " variants\n";
    }
    
    echo "\n";
    
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; exit(1); }

// 3. Create multiple orders with different scenarios
echo "3. Creating Multiple Orders\n\n";

// Create orders dynamically based on available variants
$orders = [];
$variantCount = count($availableVariants);

if ($variantCount >= 3) {
    $orders[] = [
        'customer_name' => 'أحمد محمد',
        'customer_phone' => '07901234567',
        'customer_address' => 'بغداد، الكرادة، شارع فلسطين',
        'city' => 'بغداد',
        'notes' => 'طلب لأحمد - منتجات متعددة',
        'shipping_fee' => 15,
        'items' => [
            ['variant_id' => $availableVariants[0]['id'], 'quantity' => 2],
            ['variant_id' => $availableVariants[1]['id'], 'quantity' => 1],
            ['variant_id' => $availableVariants[2]['id'], 'quantity' => 3]
        ]
    ];
}

if ($variantCount >= 5) {
    $orders[] = [
        'customer_name' => 'فاطمة علي',
        'customer_phone' => '07801234568',
        'customer_address' => 'أربيل، شارع 60 متر',
        'city' => 'أربيل',
        'notes' => 'طلب لفاطمة - بنات فقط',
        'shipping_fee' => 20,
        'items' => [
            ['variant_id' => $availableVariants[3]['id'], 'quantity' => 2],
            ['variant_id' => $availableVariants[4]['id'], 'quantity' => 1]
        ]
    ];
}

if ($variantCount >= 7) {
    $orders[] = [
        'customer_name' => 'محمد حسن',
        'customer_phone' => '07701234569',
        'customer_address' => 'البصرة، شارع العرب',
        'city' => 'البصرة',
        'notes' => 'طلب لمحمد - أولاد فقط',
        'shipping_fee' => 25,
        'items' => [
            ['variant_id' => $availableVariants[5]['id'], 'quantity' => 1],
            ['variant_id' => $availableVariants[6]['id'], 'quantity' => 2]
        ]
    ];
}

if ($variantCount >= 9) {
    $orders[] = [
        'customer_name' => 'سارة أحمد',
        'customer_phone' => '07501234570',
        'customer_address' => 'دهوك، شارع النخيل',
        'city' => 'دهوك',
        'notes' => 'طلب لسارة - منتجات موحدة',
        'shipping_fee' => 18,
        'items' => [
            ['variant_id' => $availableVariants[7]['id'], 'quantity' => 4],
            ['variant_id' => $availableVariants[8]['id'], 'quantity' => 2]
        ]
    ];
}

// Always add at least one simple order
if (empty($orders) && $variantCount >= 2) {
    $orders[] = [
        'customer_name' => 'علي خالد',
        'customer_phone' => '07301234571',
        'customer_address' => 'النجف، شارع الإمام علي',
        'city' => 'النجف',
        'notes' => 'طلب لعلي - منتجات متنوعة',
        'shipping_fee' => 12,
        'items' => [
            ['variant_id' => $availableVariants[0]['id'], 'quantity' => 1],
            ['variant_id' => $availableVariants[1]['id'], 'quantity' => 1]
        ]
    ];
} elseif (empty($orders) && $variantCount >= 1) {
    $orders[] = [
        'customer_name' => 'علي خالد',
        'customer_phone' => '07301234571',
        'customer_address' => 'النجف، شارع الإمام علي',
        'city' => 'النجف',
        'notes' => 'طلب لعلي - منتج واحد',
        'shipping_fee' => 12,
        'items' => [
            ['variant_id' => $availableVariants[0]['id'], 'quantity' => 2]
        ]
    ];
}

$createdOrders = [];

foreach ($orders as $index => $orderData) {
    echo "3." . ($index + 1) . " Creating order for: {$orderData['customer_name']}\n";
    
    try {
        // Prepare request data
        $requestData = [
            'method' => 'POST',
            'url' => '/api/checkout',
            'headers' => ['Accept' => 'application/json'],
            'body' => $orderData
        ];
        
        file_put_contents("$jsonDir/order_" . ($index + 1) . "_request.json", json_encode($requestData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Create order
        $r = $client->post("{$baseUrl}/api/checkout", [
            'headers' => ['Accept' => 'application/json'],
            'json' => $orderData
        ]);
        
        $response = json_decode($r->getBody(), true);
        echo "   Status: {$r->getStatusCode()}\n";
        echo "   Order Number: " . ($response['data']['order_number'] ?? 'N/A') . "\n";
        echo "   Total: " . ($response['data']['total'] ?? 'N/A') . "\n";
        echo "   Items: " . count($response['data']['items'] ?? []) . "\n";
        
        file_put_contents("$jsonDir/order_" . ($index + 1) . "_response.json", json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "   ✅ Saved: order_" . ($index + 1) . "_request.json, order_" . ($index + 1) . "_response.json\n";
        
        $createdOrders[] = $response['data'];
        
        // Calculate and show items details
        $subtotal = 0;
        foreach ($orderData['items'] as $item) {
            $variant = collect($availableVariants)->firstWhere('id', $item['variant_id']);
            if ($variant) {
                $itemTotal = $variant['price'] * $item['quantity'];
                $subtotal += $itemTotal;
                echo "     - {$variant['product_name']} ({$variant['color']}/{$variant['size']}) x{$item['quantity']} = {$itemTotal}\n";
            }
        }
        echo "     Subtotal: {$subtotal}\n";
        echo "     Shipping: {$orderData['shipping_fee']}\n";
        echo "     Total: " . ($subtotal + $orderData['shipping_fee']) . "\n\n";
        
    } catch(Exception $e) { 
        echo "   ❌ FAIL: ".$e->getMessage()."\n";
        if ($e->hasResponse()) {
            file_put_contents("$jsonDir/order_" . ($index + 1) . "_error.json", $e->getResponse()->getBody()->getContents());
            echo "   Error saved to: order_" . ($index + 1) . "_error.json\n";
        }
        echo "\n";
    }
}

// 4. Summary
echo "=== Summary ===\n";
echo "Total orders created: " . count($createdOrders) . "\n";
echo "Total revenue: " . array_sum(array_column($createdOrders, 'total')) . "\n";
echo "Total items sold: " . array_sum(array_column(array_map(function($order) { return $order['items'] ?? []; }, $createdOrders), 'quantity')) . "\n\n";

echo "=== Order Details ===\n";
foreach ($createdOrders as $index => $order) {
    echo ($index + 1) . ". {$order['order_number']} - {$order['customer_name']} - {$order['total']} - {$order['status']}\n";
}

echo "\n=== Files Saved ===\n";
echo "Directory: {$jsonDir}\n";
echo "Files: order_*_request.json, order_*_response.json\n\n";

echo "=== Next Steps ===\n";
echo "1. Check orders: GET /api/admin/orders\n";
echo "2. Update status: PATCH /api/admin/orders/{id}/status\n";
echo "3. View stock movements: GET /api/admin/variants/{id}/stock-movements\n\n";
