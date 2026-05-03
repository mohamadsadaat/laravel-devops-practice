<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Test Direct Product Sales Method ===\n\n";

// 1. Test the method directly
echo "1. Test the method directly\n";
try {
    $controller = new \App\Http\Controllers\Api\Admin\ProductSalesController();
    
    $response = $controller->index();
    $data = $response->getData(true);
    
    echo "   Status: " . $response->getStatusCode() . "\n";
    echo "   Products: " . $data['summary']['total_products'] . "\n";
    echo "   Variants: " . $data['summary']['total_variants'] . "\n";
    echo "   Total Sold: " . $data['summary']['total_items_sold'] . "\n";
    echo "   Total Remaining: " . $data['summary']['total_items_remaining'] . "\n";
    
    echo "   ✅ SUCCESS!\n\n";
    
    // Show detailed product information
    echo "   Product Sales Details:\n";
    foreach ($data['data'] as $product) {
        echo "     - منتج: {$product['name']}\n";
        echo "       * إجمالي المباع: {$product['total_sold']} قطعة\n";
        echo "       * إجمالي المتبقي: {$product['total_remaining']} قطعة\n";
        echo "       * عدد الفارينتس: {$product['variants_count']}\n";
        
        foreach ($product['variants'] as $variant) {
            echo "         - فارينت: {$variant['sku']} - {$variant['color_name']}/{$variant['size_name']}/{$variant['age_label']}\n";
            echo "           * المباع: {$variant['total_sold']} قطعة\n";
            echo "           * المتبقي: {$variant['available_quantity']} قطعة\n";
            echo "           * السعر: {$variant['price']}\n";
        }
        echo "\n";
    }
    
} catch(Exception $e) { 
    echo "   FAIL: ".$e->getMessage()."\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n\n";
}

echo "=== Test Complete ===\n";
