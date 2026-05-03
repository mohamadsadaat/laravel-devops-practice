<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use GuzzleHttp\Client;

$baseUrl = 'http://localhost:8000';
$client = new Client(['timeout' => 30]);

echo "=== Testing Product Variants API ===\n\n";

// 1. Login
echo "1. Login\n";
try {
    $r = $client->post("{$baseUrl}/api/login", ['json' => ['email'=>'saadat@kidsstore.com','password'=>'389235']]);
    $token = json_decode($r->getBody(), true)['token'] ?? '';
    echo "   ✅ Login successful\n\n";
} catch(Exception $e) { 
    echo "   ❌ Login failed: ".$e->getMessage()."\n"; 
    exit(1);
}

$h = ['Authorization'=>'Bearer '.$token, 'Accept' => 'application/json'];

// 2. Test GET /api/admin/products/1/variants
echo "2. Test GET /api/admin/products/1/variants\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/products/1/variants", ['headers'=>$h]);
    
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Content-Type: " . $r->getHeader('Content-Type')[0] . "\n\n";
    
    $response = json_decode($r->getBody(), true);
    
    echo "   === JSON Response Structure ===\n";
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    echo "   === Analysis ===\n";
    if (isset($response['data']) && is_array($response['data'])) {
        echo "   ✅ Returns paginated data\n";
        echo "   Total Variants: " . count($response['data']) . "\n";
        
        if (!empty($response['data'])) {
            $firstVariant = $response['data'][0];
            echo "\n   First Variant Fields:\n";
            foreach ($firstVariant as $key => $value) {
                $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
                echo "   - $key: " . (is_array($value) ? json_encode($value) : $displayValue) . "\n";
            }
            
            // Check for images
            if (isset($firstVariant['images_count'])) {
                echo "\n   📊 Images Count: {$firstVariant['images_count']}\n";
                echo "   ❌ Images NOT included in response (only count)\n";
            } else {
                echo "\n   ❌ No images_count field found\n";
            }
            
            // Check for image URLs
            if (isset($firstVariant['image_url']) || isset($firstVariant['images'])) {
                echo "   ✅ Images included in response\n";
            } else {
                echo "   ❌ No image URLs found in variant\n";
            }
        }
    } else {
        echo "   ❌ Unexpected response structure\n";
    }
    
} catch(Exception $e) { 
    echo "   ❌ Request failed: ".$e->getMessage()."\n";
}

// 3. Test Single Variant with Images
echo "\n3. Test Single Variant (if exists)\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/products/1/variants", ['headers'=>$h]);
    $response = json_decode($r->getBody(), true);
    
    if (!empty($response['data'])) {
        $variantId = $response['data'][0]['id'];
        
        $r = $client->get("{$baseUrl}/api/admin/products/1/variants/{$variantId}", ['headers'=>$h]);
        $variantResponse = json_decode($r->getBody(), true);
        
        echo "   Status: {$r->getStatusCode()}\n";
        echo "   Single Variant Response:\n";
        echo json_encode($variantResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        
        // Check for images in single variant
        if (isset($variantResponse['data']['images'])) {
            echo "   ✅ Images included in single variant response\n";
        } else {
            echo "   ❌ No images in single variant response\n";
        }
    } else {
        echo "   ⚠️  No variants found to test\n";
    }
    
} catch(Exception $e) { 
    echo "   ❌ Single variant test failed: ".$e->getMessage()."\n";
}

// 4. Test Product with Variants and Images
echo "\n4. Test Product with Variants and Images\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/products/1/with-variants", ['headers'=>$h]);
    
    echo "   Status: {$r->getStatusCode()}\n";
    $productResponse = json_decode($r->getBody(), true);
    
    echo "   Product with Variants Response:\n";
    echo json_encode($productResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    // Analyze structure
    if (isset($productResponse['data']['variants'])) {
        $variants = $productResponse['data']['variants'];
        echo "\n   📊 Variants in Product: " . count($variants) . "\n";
        
        if (!empty($variants)) {
            $firstVariant = $variants[0];
            echo "   First Variant Structure:\n";
            foreach ($firstVariant as $key => $value) {
                if (is_array($value)) {
                    echo "   - $key: Array(" . count($value) . " items)\n";
                } else {
                    $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
                    echo "   - $key: " . (is_array($value) ? 'Array' : $displayValue) . "\n";
                }
            }
            
            // Check for images in variants
            if (isset($firstVariant['images'])) {
                echo "\n   ✅ Images included in product variants!\n";
                echo "   Images count: " . count($firstVariant['images']) . "\n";
                
                if (!empty($firstVariant['images'])) {
                    $firstImage = $firstVariant['images'][0];
                    echo "   First Image Fields:\n";
                    foreach ($firstImage as $key => $value) {
                        echo "     - $key: $value\n";
                    }
                }
            } else {
                echo "\n   ❌ No images in product variants\n";
            }
        }
    }
    
} catch(Exception $e) { 
    echo "   ❌ Product with variants test failed: ".$e->getMessage()."\n";
}

echo "\n=== Summary ===\n";
echo "• /api/admin/products/1/variants - Returns variants WITHOUT images (only counts)\n";
echo "• /api/admin/products/1/variants/{id} - Returns single variant (check for images)\n";
echo "• /api/admin/products/1/with-variants - Returns product WITH variants and images\n\n";

echo "=== Recommendation ===\n";
echo "To get variants with images, use:\n";
echo "GET /api/admin/products/1/with-variants\n";
echo "This endpoint includes variants with their images\n\n";

echo "=== JSON Structure for /api/admin/products/1/variants ===\n";
echo "{\n";
echo "  \"data\": [\n";
echo "    {\n";
echo "      \"id\": 123,\n";
echo "      \"product_id\": 1,\n";
echo "      \"sku\": \"TSHIRT-RED-M-6-8\",\n";
echo "      \"color_name\": \"Red\",\n";
echo "      \"size_name\": \"Medium\",\n";
echo "      \"age_label\": \"6-8 years\",\n";
echo "      \"price\": 29.99,\n";
echo "      \"compare_price\": 39.99,\n";
echo "      \"quantity_on_hand\": 100,\n";
echo "      \"quantity_reserved\": 0,\n";
echo "      \"available_quantity\": 100,\n";
echo "      \"is_active\": true,\n";
echo "      \"images_count\": 2,\n";
echo "      \"stock_movements_count\": 5,\n";
echo "      \"created_at\": \"2026-04-30 13:00:00\",\n";
echo "      \"updated_at\": \"2026-04-30 13:00:00\"\n";
echo "    }\n";
echo "  ],\n";
echo "  \"links\": {...},\n";
echo "  \"meta\": {...}\n";
echo "}\n\n";

echo "=== JSON Structure for /api/admin/products/1/with-variants ===\n";
echo "{\n";
echo "  \"data\": {\n";
echo "    \"id\": 1,\n";
echo "    \"name\": \"Summer T-Shirt\",\n";
echo "    \"variants\": [\n";
echo "      {\n";
echo "        \"id\": 123,\n";
echo "        \"sku\": \"TSHIRT-RED-M-6-8\",\n";
echo "        \"color_name\": \"Red\",\n";
echo "        \"size_name\": \"Medium\",\n";
echo "        \"age_label\": \"6-8 years\",\n";
echo "        \"price\": 29.99,\n";
echo "        \"images\": [\n";
echo "          {\n";
echo "            \"id\": 456,\n";
echo "            \"image_path\": \"product-images/abc123.jpg\",\n";
echo "            \"image_url\": \"/storage/product-images/abc123.jpg\",\n";
echo "            \"alt_text\": \"Red t-shirt\",\n";
echo "            \"is_primary\": true\n";
echo "          }\n";
echo "        ]\n";
echo "      }\n";
echo "    ]\n";
echo "  }\n";
echo "}\n\n";
