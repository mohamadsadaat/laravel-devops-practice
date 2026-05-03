<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use GuzzleHttp\Client;

$baseUrl = 'http://10.193.164.192:8000';
$client = new Client(['timeout' => 30]);
$jsonDir = __DIR__ . '/test_json/single_product_sales';
@mkdir($jsonDir, 0777, true);

echo "=== Test Single Product Sales API ===\n\n";

// 1. Login
echo "1. Login\n";
try {
    $r = $client->post("{$baseUrl}/api/login", ['json' => ['email'=>'saadat@kidsstore.com','password'=>'389235']]);
    $token = json_decode($r->getBody(), true)['token'] ?? '';
    echo "   OK\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; exit(1); }

$h = ['Authorization'=>'Bearer '.$token, 'Accept' => 'application/json'];

// 2. Test GET /api/products/sales (all products with sales data)
echo "2. Test GET /api/products/sales\n";
try {
    $r = $client->get("{$baseUrl}/api/products/sales", ['headers'=>$h]);
    $body = json_decode($r->getBody(), true);
    echo "   Status: {$r->getStatusCode()}\n";
    echo "   Products: " . $body['summary']['total_products'] . "\n";
    echo "   Variants: " . $body['summary']['total_variants'] . "\n";
    echo "   Total Sold: " . $body['summary']['total_items_sold'] . "\n";
    echo "   Total Remaining: " . $body['summary']['total_items_remaining'] . "\n";
    
    file_put_contents("$jsonDir/product_sales_request.json", json_encode([
        'method' => 'GET',
        'url' => '/api/products/sales',
        'headers' => [
            'Authorization' => 'Bearer {token}',
            'Accept' => 'application/json'
        ],
        'auth_required' => true
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    file_put_contents("$jsonDir/product_sales_response.json", json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    echo "   ✅ Saved: product_sales_request.json, product_sales_response.json\n\n";
    
    // Show detailed product information
    echo "   Product Sales Details:\n";
    foreach ($body['data'] as $product) {
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
    if ($e->hasResponse()) {
        file_put_contents("$jsonDir/product_sales_error.json", $e->getResponse()->getBody()->getContents());
        echo "   Error saved to: product_sales_error.json\n";
    }
    echo "\n";
}

echo "=== Test Complete ===\n";
echo "Files saved to: {$jsonDir}\n\n";
echo "=== Product Sales API Summary ===\n";
echo "Endpoint: GET /api/products/sales\n";
echo "Auth: Required (Bearer Token)\n";
echo "Method: GET\n";
echo "Response: JSON with product sales and inventory data\n\n";

echo "=== Response Structure Example ===\n";
echo "{\n";
echo "    \"data\": [\n";
echo "        {\n";
echo "            \"id\": 1,\n";
echo "            \"name\": \"شورت\",\n";
echo "            \"slug\": \"short\",\n";
echo "            \"base_price\": \"25.99\",\n";
echo "            \"brand\": \"Nike\",\n";
echo "            \"gender\": \"boy\",\n";
echo "            \"status\": \"active\",\n";
echo "            \"total_sold\": 20,\n";
echo "            \"total_remaining\": 30,\n";
echo "            \"variants_count\": 3,\n";
echo "            \"variants\": [\n";
echo "                {\n";
echo "                    \"id\": 1,\n";
echo "                    \"sku\": \"SHRT-001\",\n";
echo "                    \"color_name\": \"أزرق\",\n";
echo "                    \"size_name\": \"S\",\n";
echo "                    \"age_label\": \"2-4 سنوات\",\n";
echo "                    \"price\": \"25.99\",\n";
echo "                    \"quantity_on_hand\": 15,\n";
echo "                    \"quantity_reserved\": 0,\n";
echo "                    \"available_quantity\": 15,\n";
echo "                    \"total_sold\": 7\n";
echo "                },\n";
echo "                {\n";
echo "                    \"id\": 2,\n";
echo "                    \"sku\": \"SHRT-002\",\n";
echo "                    \"color_name\": \"أحمر\",\n";
echo "                    \"size_name\": \"M\",\n";
echo "                    \"age_label\": \"4-6 سنوات\",\n";
echo "                    \"price\": \"25.99\",\n";
echo "                    \"quantity_on_hand\": 10,\n";
echo "                    \"quantity_reserved\": 0,\n";
echo "                    \"available_quantity\": 10,\n";
echo "                    \"total_sold\": 3\n";
echo "                },\n";
echo "                {\n";
echo "                    \"id\": 3,\n";
echo "                    \"sku\": \"SHRT-003\",\n";
echo "                    \"color_name\": \"أخضر\",\n";
echo "                    \"size_name\": \"L\",\n";
echo "                    \"age_label\": \"6-8 سنوات\",\n";
echo "                    \"price\": \"25.99\",\n";
echo "                    \"quantity_on_hand\": 5,\n";
echo "                    \"quantity_reserved\": 0,\n";
echo "                    \"available_quantity\": 5,\n";
echo "                    \"total_sold\": 10\n";
echo "                }\n";
echo "            ]\n";
echo "        }\n";
echo "    ],\n";
echo "    \"summary\": {\n";
echo "        \"total_products\": 5,\n";
echo "        \"total_variants\": 15,\n";
echo "        \"total_items_sold\": 45,\n";
echo "        \"total_items_remaining\": 120\n";
echo "    }\n";
echo "}\n\n";

echo "=== Frontend Usage Example ===\n";
echo "// Get all products sales data\n";
echo "const getProductsSales = async () => {\n";
echo "    const token = localStorage.getItem('token');\n";
echo "    const response = await fetch('http://10.193.164.192:8000/api/products/sales', {\n";
echo "        headers: {\n";
echo "            'Authorization': `Bearer \${token}`,\n";
echo "            'Accept': 'application/json'\n";
echo "        }\n";
echo "    });\n";
echo "    \n";
echo "    if (!response.ok) {\n";
echo "        throw new Error('Failed to fetch products sales data');\n";
echo "    }\n";
echo "    \n";
echo "    return await response.json();\n";
echo "};\n\n";

echo "=== Dashboard Component Example ===\n";
echo "const ProductSalesDashboard = () => {\n";
echo "    const [salesData, setSalesData] = useState(null);\n";
echo "    \n";
echo "    useEffect(() => {\n";
echo "        getProductsSales()\n";
echo "            .then(setSalesData)\n";
echo "            .catch(console.error);\n";
echo "    }, []);\n";
echo "    \n";
echo "    if (!salesData) return <div>Loading...</div>;\n";
echo "    \n";
echo "    return (\n";
echo "        <div className=\"sales-dashboard\">\n";
echo "            <div className=\"summary\">\n";
echo "                <h3>ملخص المبيعات</h3>\n";
echo "                <p>المنتجات: {salesData.summary.total_products}</p>\n";
echo "                <p>الفارينتس: {salesData.summary.total_variants}</p>\n";
echo "                <p>إجمالي المباع: {salesData.summary.total_items_sold} قطعة</p>\n";
echo "                <p>إجمالي المتبقي: {salesData.summary.total_items_remaining} قطعة</p>\n";
echo "            </div>\n";
echo "            \n";
echo "            <div className=\"products-list\">\n";
echo "                <h3>تفاصيل المنتجات</h3>\n";
echo "                {salesData.data.map(product => (\n";
echo "                    <div key={product.id} className=\"product-card\">\n";
echo "                        <h4>{product.name}</h4>\n";
echo "                        <p>المباع: {product.total_sold} قطعة</p>\n";
echo "                        <p>المتبقي: {product.total_remaining} قطعة</p>\n";
echo "                        \n";
echo "                        <div className=\"variants\">\n";
echo "                            <h5>الفارينتس:</h5>\n";
echo "                            {product.variants.map(variant => (\n";
echo "                                <div key={variant.id} className=\"variant-item\">\n";
echo "                                    <span>{variant.sku} - {variant.color_name}/{variant.size_name}</span>\n";
echo "                                    <span>المباع: {variant.total_sold} قطعة</span>\n";
echo "                                    <span>المتبقي: {variant.available_quantity} قطعة</span>\n";
echo "                                    <span>السعر: {variant.price}</span>\n";
echo "                                </div>\n";
echo "                            ))}\n";
echo "                        </div>\n";
echo "                    </div>\n";
echo "                ))}\n";
echo "            </div>\n";
echo "        </div>\n";
echo "    );\n";
echo "};\n\n";
