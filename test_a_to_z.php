<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Http;

$baseUrl = 'http://10.193.164.192:8000';
$client = new GuzzleHttp\Client(['timeout' => 30]);

echo "=== A-to-Z Test ===\n\n";

// 1. Login
echo "1. Login\n";
try {
    $r = $client->post("{$baseUrl}/api/login", ['json' => ['email'=>'saadat@kidsstore.com','password'=>'389235']]);
    $token = json_decode($r->getBody(), true)['token'] ?? '';
    echo "   OK\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; exit(1); }

$h = ['Authorization'=>'Bearer '.$token, 'Accept'=>'application/json'];

// 2. Create Product
echo "2. Create Product\n";
$cat = Category::first();
try {
    $r = $client->post("{$baseUrl}/api/admin/products", ['headers'=>$h, 'json'=>[
        'category_id'=>$cat->id, 'name'=>'Test_'.time(), 'status'=>'active', 'base_price'=>500
    ]]);
    $pid = json_decode($r->getBody(), true)['data']['id'];
    echo "   Product ID: {$pid}\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; exit(1); }

// 3. Create Variant 1
echo "3. Create Variant 1\n";
try {
    $r = $client->post("{$baseUrl}/api/admin/products/{$pid}/variants", ['headers'=>$h, 'json'=>[
        'sku'=>'RED-S-'.time(), 'color_name'=>'red', 'size_name'=>'S', 'age_label'=>'3-6m',
        'price'=>200, 'compare_price'=>300, 'quantity_on_hand'=>10, 'is_active'=>true
    ]]);
    $vid1 = json_decode($r->getBody(), true)['data']['id'];
    echo "   Variant 1 ID: {$vid1}\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; exit(1); }

// 4. Create Variant 2
echo "4. Create Variant 2\n";
try {
    $r = $client->post("{$baseUrl}/api/admin/products/{$pid}/variants", ['headers'=>$h, 'json'=>[
        'sku'=>'BLUE-M-'.time(), 'color_name'=>'blue', 'size_name'=>'M', 'age_label'=>'6-12m',
        'price'=>250, 'compare_price'=>400, 'quantity_on_hand'=>15, 'is_active'=>true
    ]]);
    $vid2 = json_decode($r->getBody(), true)['data']['id'];
    echo "   Variant 2 ID: {$vid2}\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n"; exit(1); }

// 5. Add product image (variant_id=null)
echo "5. Add product image (variant_id=null)\n";
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5+hHgAHggJ/PchI7wAAAABJRU5ErkJggg==');
$f1 = tempnam(sys_get_temp_dir(),'img').'.png'; file_put_contents($f1,$png);
try {
    $r = $client->post("{$baseUrl}/api/admin/products/{$pid}/images", ['headers'=>$h, 'multipart'=>[
        ['name'=>'image','contents'=>fopen($f1,'r'),'filename'=>'general.png'],
        ['name'=>'variant_id','contents'=>''],
        ['name'=>'alt_text','contents'=>'General image'],
        ['name'=>'is_primary','contents'=>'true'],
    ]]);
    $d = json_decode($r->getBody(), true)['data'];
    echo "   Image ID: {$d['id']}, variant_id: ".($d['variant_id'] ?? 'NULL')."\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }
unlink($f1);

// 6. Add image for Variant 1
echo "6. Add image for Variant 1 (ID={$vid1})\n";
$f2 = tempnam(sys_get_temp_dir(),'img').'.png'; file_put_contents($f2,$png);
try {
    $r = $client->post("{$baseUrl}/api/admin/products/{$pid}/images", ['headers'=>$h, 'multipart'=>[
        ['name'=>'image','contents'=>fopen($f2,'r'),'filename'=>'v1-red.png'],
        ['name'=>'variant_id','contents'=>(string)$vid1],
        ['name'=>'alt_text','contents'=>'Red variant'],
        ['name'=>'is_primary','contents'=>'true'],
    ]]);
    $d = json_decode($r->getBody(), true)['data'];
    echo "   Image ID: {$d['id']}, variant_id: {$d['variant_id']}\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }
unlink($f2);

// 7. Add image for Variant 2
echo "7. Add image for Variant 2 (ID={$vid2})\n";
$f3 = tempnam(sys_get_temp_dir(),'img').'.png'; file_put_contents($f3,$png);
try {
    $r = $client->post("{$baseUrl}/api/admin/products/{$pid}/images", ['headers'=>$h, 'multipart'=>[
        ['name'=>'image','contents'=>fopen($f3,'r'),'filename'=>'v2-blue.png'],
        ['name'=>'variant_id','contents'=>(string)$vid2],
        ['name'=>'alt_text','contents'=>'Blue variant'],
        ['name'=>'is_primary','contents'=>'true'],
    ]]);
    $d = json_decode($r->getBody(), true)['data'];
    echo "   Image ID: {$d['id']}, variant_id: {$d['variant_id']}\n\n";
} catch(Exception $e) { echo "   FAIL: ".$e->getMessage()."\n\n"; }
unlink($f3);

// 8. VERIFY DATABASE
echo "=== VERIFY DATABASE ===\n";
$images = ProductImage::where('product_id', $pid)->get();
echo "Product: {$pid}, Variant1: {$vid1}, Variant2: {$vid2}\n\n";

$ok = true;
foreach($images as $img) {
    $v = $img->variant_id ?? 'NULL';
    $status = 'OK';
    if($img->variant_id !== null && $img->variant_id != $vid1 && $img->variant_id != $vid2) {
        $status = "WRONG! Expected {$vid1} or {$vid2}, got {$img->variant_id}";
        $ok = false;
    }
    echo "  Image {$img->id}: product_id={$img->product_id}, variant_id={$v} => {$status}\n";
}

echo "\n";
echo $ok ? "✅ ALL variant_id values are CORRECT!\n" : "❌ Some variant_id values are WRONG!\n";

// 9. Verify API
echo "\n=== VERIFY API ===\n";
try {
    $r = $client->get("{$baseUrl}/api/admin/products/{$pid}/with-variants", ['headers'=>$h]);
    $res = json_decode($r->getBody(), true);
    foreach($res['product']['variants'] as $v) {
        $img = $v['image'];
        $imgs = count($v['images']);
        echo "  Variant {$v['id']}: image={$img}, images_count={$imgs}\n";
    }
} catch(Exception $e) { echo "  FAIL: ".$e->getMessage()."\n"; }

echo "\n=== DONE ===\n";
