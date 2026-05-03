<?php

use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\CollectionController;
use App\Http\Controllers\Api\Admin\InventoryController;
use App\Http\Controllers\Api\Admin\OrderController;
use App\Http\Controllers\Api\Admin\ProductController;
use App\Http\Controllers\Api\Admin\ProductImageController;
use App\Http\Controllers\Api\Admin\ProductSalesController;
use App\Http\Controllers\Api\Admin\ProductVariantController;
use App\Http\Controllers\Api\Storefront\CheckoutController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

Route::post('checkout', [CheckoutController::class, 'store']);

// Public read-only routes for products, categories and collections
Route::get('admin/categories', [CategoryController::class, 'index']);
Route::get('admin/categories/{category}', [CategoryController::class, 'show']);
Route::get('admin/collections', [CollectionController::class, 'index']);
Route::get('admin/collections/{collection}', [CollectionController::class, 'show']);
Route::get('admin/collections/{collection}/with-products', [CollectionController::class, 'showWithProducts']);

Route::get('products/sales', [ProductSalesController::class, 'index']);
Route::get('admin/products', [ProductController::class, 'index']);
Route::get('admin/products/sales', [ProductSalesController::class, 'index']);
Route::get('admin/products/{product}', [ProductController::class, 'show']);
Route::get('admin/products/{product}/with-variants', [ProductController::class, 'withVariants']);

Route::get('admin/products/{product}/variants', [ProductVariantController::class, 'index']);
Route::get('admin/products/{product}/variants/{variant}', [ProductVariantController::class, 'show']);

// Admin routes (require authentication)
Route::prefix('admin')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        // Categories - write operations
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{category}', [CategoryController::class, 'update']);
        Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
        Route::delete('categories/{category}/image', [CategoryController::class, 'removeImage']);
        
        // Products - write operations
        Route::post('products', [ProductController::class, 'store']);
        Route::put('products/{product}', [ProductController::class, 'update']);
        Route::delete('products/{product}', [ProductController::class, 'destroy']);
        Route::patch('products/{product}/archive', [ProductController::class, 'archive']);
        
        // Product Images - all operations
        Route::post('products/{product}/images', [ProductImageController::class, 'store']);
        Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy']);
        
        // Product Variants - write operations
        Route::get('variants', [ProductVariantController::class, 'globalIndex']);
        Route::post('products/{product}/variants', [ProductVariantController::class, 'store']);
        Route::put('products/{product}/variants/{variant}', [ProductVariantController::class, 'update']);
        Route::delete('products/{product}/variants/{variant}', [ProductVariantController::class, 'destroy']);
        
        // Inventory - all operations
        Route::post('variants/{variant}/inventory/adjust', [InventoryController::class, 'adjust']);
        Route::post('variants/{variant}/inventory/set', [InventoryController::class, 'set']);
        Route::get('variants/{variant}/stock-movements', [InventoryController::class, 'movements']);
       
        // Collections - all operations
        Route::post('collections', [CollectionController::class, 'store']);
        Route::put('collections/{collection}', [CollectionController::class, 'update']);
        Route::delete('collections/{collection}', [CollectionController::class, 'destroy']);
        
        // Collection Products Management
        Route::post('collections/{collection}/products/add', [CollectionController::class, 'addProducts']);
        Route::post('collections/{collection}/products/remove', [CollectionController::class, 'removeProducts']);
        Route::post('collections/{collection}/products/sync', [CollectionController::class, 'syncProducts']);
        
        // Orders - all operations
        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/active-count', [OrderController::class, 'activeCount']);
        Route::get('orders/{order}', [OrderController::class, 'show']);
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus']);

    });
