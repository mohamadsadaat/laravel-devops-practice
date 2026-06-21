<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample products and variants
        $category = Category::first();
        
        if ($category) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => 'Kids T-Shirt',
                'slug' => 'kids-t-shirt',
                'description' => 'Comfortable cotton t-shirt for kids',
                'status' => 'active',
                'base_price' => 29.99,
                'brand' => 'KidsWear',
                'gender' => 'unisex',
                'is_featured' => true,
            ]);

            // Create sample variants
            $variants = [
                [
                    'sku' => 'KTS-3-5Y',
                    'age_label' => '3-5 Years',
                    'quantity_on_hand' => 50,
                    'quantity_reserved' => 5,
                    'is_active' => true,
                ],
                [
                    'sku' => 'KTS-6-8Y',
                    'age_label' => '6-8 Years',
                    'quantity_on_hand' => 30,
                    'quantity_reserved' => 2,
                    'is_active' => true,
                ],
                [
                    'sku' => 'KTS-9-11Y',
                    'age_label' => '9-11 Years',
                    'quantity_on_hand' => 25,
                    'quantity_reserved' => 0,
                    'is_active' => true,
                ],
            ];

            foreach ($variants as $variantData) {
                $product->variants()->create($variantData);
            }

            $this->command->info('Sample data created successfully!');
            $this->command->info('Product: ' . $product->name);
            $this->command->info('Variants created: ' . count($variants));
        } else {
            $this->command->warning('No categories found. Please create categories first.');
        }
    }
}
