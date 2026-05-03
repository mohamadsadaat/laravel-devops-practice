<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;

echo "=== Add Test Products with Gender ===\n\n";

// Get category
$category = Category::first();
if (!$category) {
    echo "No category found. Creating test category...\n";
    $category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category-' . time(),
        'description' => 'Test category for gender filtering'
    ]);
    echo "Created category: {$category->name} (ID: {$category->id})\n";
}

// Create products with different genders
$products = [
    [
        'name' => 'Boys Blue T-Shirt',
        'slug' => 'boys-blue-tshirt-' . time(),
        'description' => 'Comfortable blue t-shirt for boys',
        'category_id' => $category->id,
        'base_price' => 25.99,
        'brand' => 'KidsBrand',
        'gender' => 'boy',
        'status' => 'active'
    ],
    [
        'name' => 'Girls Pink Dress',
        'slug' => 'girls-pink-dress-' . time(),
        'description' => 'Beautiful pink dress for girls',
        'category_id' => $category->id,
        'base_price' => 35.99,
        'brand' => 'KidsBrand',
        'gender' => 'girl',
        'status' => 'active'
    ],
    [
        'name' => 'Unisex Yellow Hoodie',
        'slug' => 'unisex-yellow-hoodie-' . time(),
        'description' => 'Warm yellow hoodie for all kids',
        'category_id' => $category->id,
        'base_price' => 45.99,
        'brand' => 'KidsBrand',
        'gender' => 'unisex',
        'status' => 'active'
    ]
];

foreach ($products as $productData) {
    $product = Product::create($productData);
    echo "Created: {$product->name} (Gender: {$product->gender}, ID: {$product->id})\n";
}

echo "\n=== Test Data Added ===\n";
echo "Now you can test the gender filtering API:\n";
echo "GET /api/admin/products?gender=boy\n";
echo "GET /api/admin/products?gender=girl\n";
echo "GET /api/admin/products?gender=unisex\n\n";
