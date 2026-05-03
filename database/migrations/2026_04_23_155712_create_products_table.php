<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');

            $table->decimal('base_price', 10, 2)->nullable();
            $table->string('brand')->nullable();
            $table->enum('gender', ['boy', 'girl', 'unisex'])->nullable();

            $table->boolean('is_featured')->default(false);

            $table->timestamps();

            $table->index(['category_id', 'status']);
            $table->index(['status', 'is_featured']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
