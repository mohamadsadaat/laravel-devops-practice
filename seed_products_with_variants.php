<?php
require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

echo "=== Seeding 50 Products with Variants and Images ===\n\n";

// Categories for products
$categories = [
    ['name' => 'T-Shirts', 'slug' => 't-shirts', 'description' => 'Comfortable t-shirts for kids'],
    ['name' => 'Pants', 'slug' => 'pants', 'description' => 'Stylish pants for boys and girls'],
    ['name' => 'Dresses', 'slug' => 'dresses', 'description' => 'Beautiful dresses for girls'],
    ['name' => 'Jackets', 'slug' => 'jackets', 'description' => 'Warm jackets for all seasons'],
    ['name' => 'Shoes', 'slug' => 'shoes', 'description' => 'Comfortable shoes for active kids'],
    ['name' => 'Accessories', 'slug' => 'accessories', 'description' => 'Fun accessories for kids'],
    ['name' => 'Sleepwear', 'slug' => 'sleepwear', 'description' => 'Cozy sleepwear for good night sleep'],
    ['name' => 'Sportswear', 'slug' => 'sportswear', 'description' => 'Active wear for sports and play'],
];

// Ensure categories exist
foreach ($categories as $categoryData) {
    Category::firstOrCreate(
        ['slug' => $categoryData['slug']],
        $categoryData
    );
}

echo "Categories created/verified.\n";

// Product data templates
$productTemplates = [
    // T-Shirts
    [
        'category' => 't-shirts',
        'products' => [
            ['name' => 'Cotton Comfort T-Shirt', 'brand' => 'KidsComfort', 'gender' => 'unisex', 'base_price' => 19.99],
            ['name' => 'Graphic Print T-Shirt', 'brand' => 'FunWear', 'gender' => 'unisex', 'base_price' => 22.99],
            ['name' => 'Striped Cotton T-Shirt', 'brand' => 'KidsStyle', 'gender' => 'unisex', 'base_price' => 21.99],
            ['name' => 'Organic Cotton T-Shirt', 'brand' => 'EcoKids', 'gender' => 'unisex', 'base_price' => 24.99],
            ['name' => 'Polo T-Shirt', 'brand' => 'SportyKids', 'gender' => 'boy', 'base_price' => 26.99],
            ['name' => 'Floral Print T-Shirt', 'brand' => 'GirlPower', 'gender' => 'girl', 'base_price' => 23.99],
        ]
    ],
    // Pants
    [
        'category' => 'pants',
        'products' => [
            ['name' => 'Denim Jeans', 'brand' => 'DenimKids', 'gender' => 'unisex', 'base_price' => 34.99],
            ['name' => 'Cargo Pants', 'brand' => 'AdventureKids', 'gender' => 'boy', 'base_price' => 32.99],
            ['name' => 'Leggings', 'brand' => 'ComfortFit', 'gender' => 'girl', 'base_price' => 18.99],
            ['name' => 'Chino Pants', 'brand' => 'ClassicKids', 'gender' => 'boy', 'base_price' => 29.99],
            ['name' => 'Jogger Pants', 'brand' => 'ActiveKids', 'gender' => 'unisex', 'base_price' => 25.99],
            ['name' => 'Shorts', 'brand' => 'SummerKids', 'gender' => 'unisex', 'base_price' => 16.99],
        ]
    ],
    // Dresses
    [
        'category' => 'dresses',
        'products' => [
            ['name' => 'Summer Dress', 'brand' => 'Sunshine', 'gender' => 'girl', 'base_price' => 28.99],
            ['name' => 'Party Dress', 'brand' => 'Princess', 'gender' => 'girl', 'base_price' => 45.99],
            ['name' => 'Casual Dress', 'brand' => 'Everyday', 'gender' => 'girl', 'base_price' => 24.99],
            ['name' => 'Floral Dress', 'brand' => 'Garden', 'gender' => 'girl', 'base_price' => 32.99],
            ['name' => 'Maxi Dress', 'brand' => 'Elegant', 'gender' => 'girl', 'base_price' => 38.99],
            ['name' => 'Tutu Dress', 'brand' => 'Ballet', 'gender' => 'girl', 'base_price' => 35.99],
        ]
    ],
    // Jackets
    [
        'category' => 'jackets',
        'products' => [
            ['name' => 'Denim Jacket', 'brand' => 'Classic', 'gender' => 'unisex', 'base_price' => 42.99],
            ['name' => 'Winter Coat', 'brand' => 'Warmth', 'gender' => 'unisex', 'base_price' => 65.99],
            ['name' => 'Rain Jacket', 'brand' => 'WeatherGuard', 'gender' => 'unisex', 'base_price' => 38.99],
            ['name' => 'Leather Jacket', 'brand' => 'CoolKids', 'gender' => 'boy', 'base_price' => 55.99],
            ['name' => 'Bomber Jacket', 'brand' => 'Urban', 'gender' => 'boy', 'base_price' => 48.99],
            ['name' => 'Windbreaker', 'brand' => 'Sporty', 'gender' => 'unisex', 'base_price' => 35.99],
        ]
    ],
    // Shoes
    [
        'category' => 'shoes',
        'products' => [
            ['name' => 'Sneakers', 'brand' => 'ActiveStep', 'gender' => 'unisex', 'base_price' => 39.99],
            ['name' => 'Sandals', 'brand' => 'SummerStep', 'gender' => 'unisex', 'base_price' => 24.99],
            ['name' => 'Boots', 'brand' => 'Adventure', 'gender' => 'unisex', 'base_price' => 45.99],
            ['name' => 'School Shoes', 'brand' => 'Study', 'gender' => 'unisex', 'base_price' => 35.99],
            ['name' => 'Running Shoes', 'brand' => 'Speed', 'gender' => 'unisex', 'base_price' => 42.99],
            ['name' => 'Dress Shoes', 'brand' => 'Formal', 'gender' => 'unisex', 'base_price' => 38.99],
        ]
    ],
    // Accessories
    [
        'category' => 'accessories',
        'products' => [
            ['name' => 'Baseball Cap', 'brand' => 'Sporty', 'gender' => 'unisex', 'base_price' => 12.99],
            ['name' => 'Backpack', 'brand' => 'School', 'gender' => 'unisex', 'base_price' => 28.99],
            ['name' => 'Sunglasses', 'brand' => 'Shade', 'gender' => 'unisex', 'base_price' => 15.99],
            ['name' => 'Watch', 'brand' => 'Time', 'gender' => 'unisex', 'base_price' => 22.99],
            ['name' => 'Hair Clips', 'brand' => 'Pretty', 'gender' => 'girl', 'base_price' => 8.99],
            ['name' => 'Belt', 'brand' => 'Classic', 'gender' => 'unisex', 'base_price' => 14.99],
        ]
    ],
    // Sleepwear
    [
        'category' => 'sleepwear',
        'products' => [
            ['name' => 'Pajama Set', 'brand' => 'Cozy', 'gender' => 'unisex', 'base_price' => 26.99],
            ['name' => 'Nightgown', 'brand' => 'Dream', 'gender' => 'girl', 'base_price' => 22.99],
            ['name' => 'Sleep Shirt', 'brand' => 'Comfort', 'gender' => 'unisex', 'base_price' => 18.99],
            ['name' => 'Robe', 'brand' => 'Luxury', 'gender' => 'unisex', 'base_price' => 32.99],
            ['name' => 'Slippers', 'brand' => 'Soft', 'gender' => 'unisex', 'base_price' => 16.99],
            ['name' => 'Sleep Shorts', 'brand' => 'Cool', 'gender' => 'unisex', 'base_price' => 14.99],
        ]
    ],
    // Sportswear
    [
        'category' => 'sportswear',
        'products' => [
            ['name' => 'Sports Jersey', 'brand' => 'Team', 'gender' => 'unisex', 'base_price' => 32.99],
            ['name' => 'Track Pants', 'brand' => 'Athletic', 'gender' => 'unisex', 'base_price' => 28.99],
            ['name' => 'Sports Shorts', 'brand' => 'Active', 'gender' => 'unisex', 'base_price' => 19.99],
            ['name' => 'Sweatshirt', 'brand' => 'Warm', 'gender' => 'unisex', 'base_price' => 35.99],
            ['name' => 'Tank Top', 'brand' => 'Sport', 'gender' => 'unisex', 'base_price' => 16.99],
            ['name' => 'Compression Shirt', 'brand' => 'Performance', 'gender' => 'unisex', 'base_price' => 38.99],
        ]
    ],
];

// Colors, sizes, and age labels for variants
$colors = ['Red', 'Blue', 'Green', 'Yellow', 'Pink', 'Purple', 'Orange', 'Black', 'White', 'Gray'];
$sizes = ['Extra Small', 'Small', 'Medium', 'Large', 'Extra Large'];
$ageLabels = ['0-2 years', '2-4 years', '4-6 years', '6-8 years', '8-10 years', '10-12 years'];

// Image URLs (placeholder images)
$imageUrls = [
    'https://picsum.photos/seed/product1/400/400.jpg',
    'https://picsum.photos/seed/product2/400/400.jpg',
    'https://picsum.photos/seed/product3/400/400.jpg',
    'https://picsum.photos/seed/product4/400/400.jpg',
    'https://picsum.photos/seed/product5/400/400.jpg',
];

$variantImageUrls = [
    'https://picsum.photos/seed/variant1/300/300.jpg',
    'https://picsum.photos/seed/variant2/300/300.jpg',
    'https://picsum.photos/seed/variant3/300/300.jpg',
    'https://picsum.photos/seed/variant4/300/300.jpg',
    'https://picsum.photos/seed/variant5/300/300.jpg',
];

echo "Starting to seed products...\n";

$productsCreated = 0;
$totalProductsNeeded = 50;

foreach ($productTemplates as $categoryTemplate) {
    if ($productsCreated >= $totalProductsNeeded) break;
    
    $category = Category::where('slug', $categoryTemplate['category'])->first();
    
    foreach ($categoryTemplate['products'] as $productData) {
        if ($productsCreated >= $totalProductsNeeded) break;
        
        try {
            DB::beginTransaction();
            
            // Create product
            $product = Product::create([
                'name' => $productData['name'],
                'slug' => Str::slug($productData['name']) . '-' . uniqid(),
                'description' => "High quality {$productData['name']} for kids. Made with premium materials for comfort and durability.",
                'category_id' => $category->id,
                'brand' => $productData['brand'],
                'gender' => $productData['gender'],
                'base_price' => $productData['base_price'],
                'status' => 'active',
                'is_featured' => rand(0, 1) == 1,
            ]);
            
            // Create product images
            foreach ($imageUrls as $index => $imageUrl) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $imageUrl,
                    'alt_text' => "{$productData['name']} - Image " . ($index + 1),
                    'is_primary' => $index == 0,
                    'sort_order' => $index,
                ]);
            }
            
            // Create 2 variants for each product
            for ($variantIndex = 0; $variantIndex < 2; $variantIndex++) {
                $color = $colors[array_rand($colors)];
                $size = $sizes[array_rand($sizes)];
                $ageLabel = $ageLabels[array_rand($ageLabels)];
                
                // Generate SKU using the service
                $variantData = [
                    'color_name' => $color,
                    'size_name' => $size,
                    'age_label' => $ageLabel,
                    'price' => $productData['base_price'] + rand(-5, 15),
                    'compare_price' => $productData['base_price'] + rand(10, 25),
                    'quantity_on_hand' => rand(10, 100),
                    'is_active' => true,
                ];
                
                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => generateSku($product, $variantData),
                    'color_name' => $color,
                    'size_name' => $size,
                    'age_label' => $ageLabel,
                    'price' => $variantData['price'],
                    'compare_price' => $variantData['compare_price'],
                    'quantity_on_hand' => $variantData['quantity_on_hand'],
                    'quantity_reserved' => 0,
                    'is_active' => true,
                ]);
                
                // Create variant images
                foreach ($variantImageUrls as $index => $imageUrl) {
                    ProductImage::create([
                        'product_id' => $product->id,
                        'variant_id' => $variant->id,
                        'path' => $imageUrl,
                        'alt_text' => "{$productData['name']} - {$color} {$size} - Image " . ($index + 1),
                        'is_primary' => $variantIndex == 0 && $index == 0,
                        'sort_order' => $variantIndex * 10 + $index,
                    ]);
                }
            }
            
            DB::commit();
            $productsCreated++;
            
            echo "✅ Created product {$productsCreated}: {$product->name} (ID: {$product->id})\n";
            
        } catch (\Exception $e) {
            DB::rollBack();
            echo "❌ Error creating product: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n=== Seeding Complete ===\n";
echo "Total products created: {$productsCreated}\n";
echo "Total variants created: " . ($productsCreated * 2) . "\n";
echo "Total product images created: " . ($productsCreated * count($imageUrls)) . "\n";
echo "Total variant images created: " . ($productsCreated * 2 * count($variantImageUrls)) . "\n\n";

// Helper function to generate SKU (copied from service)
function generateSku($product, $data) {
    $parts = [];
    
    // Product name part (first 5 characters)
    $productPart = cleanString($product->name);
    $parts[] = substr($productPart, 0, 5);
    
    // Color part (first 4 characters)
    if (!empty($data['color_name'])) {
        $colorPart = cleanString($data['color_name']);
        $parts[] = substr($colorPart, 0, 4);
    }
    
    // Size part
    if (!empty($data['size_name'])) {
        $sizePart = cleanString($data['size_name']);
        $parts[] = substr($sizePart, 0, 3);
    }
    
    // Age part
    if (!empty($data['age_label'])) {
        $agePart = cleanString($data['age_label']);
        $parts[] = substr($agePart, 0, 3);
    }
    
    // Join parts with hyphens
    $sku = implode('-', $parts);
    
    // Limit length to 20 characters
    if (strlen($sku) > 20) {
        $sku = substr($sku, 0, 17) . '...';
    }
    
    // Make unique
    $counter = 1;
    $baseSku = strtoupper($sku);
    $sku = $baseSku;
    
    while (ProductVariant::where('sku', $sku)->exists()) {
        $sku = $baseSku . '-' . $counter;
        $counter++;
    }
    
    return $sku;
}

function cleanString($value) {
    // Remove special characters and normalize
    $clean = preg_replace('/[^a-zA-Z0-9\s\-]/', '', $value);
    $clean = preg_replace('/[\s\-]+/', '-', $clean);
    $clean = trim($clean, '-');
    return strtolower($clean);
}

echo "=== Sample Data Created ===\n";
echo "Categories: " . count($categories) . "\n";
echo "Products per category: ~6-7\n";
echo "Variants per product: 2\n";
echo "Images per product: " . count($imageUrls) . "\n";
echo "Images per variant: " . count($variantImageUrls) . "\n\n";

echo "=== Data Structure ===\n";
echo "Product: Name, Description, Category, Brand, Gender, Price, Status\n";
echo "Variant: SKU, Color, Size, Age, Price, Stock\n";
echo "Images: Product images + Variant-specific images\n\n";

echo "✅ Database seeding completed successfully!\n";
