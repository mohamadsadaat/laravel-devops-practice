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
        Schema::create('product_variants', function (Blueprint $table) {
           $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('sku')->unique();

            $table->string('color_name');
            $table->string('size_name');
            $table->string('age_label');

            $table->decimal('price', 10, 2);
            $table->decimal('compare_price', 10, 2)->nullable();

            $table->unsignedInteger('quantity_on_hand')->default(0);
            $table->unsignedInteger('quantity_reserved')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['product_id', 'color_name', 'size_name', 'age_label'], 'product_variant_unique');
            $table->index(['product_id', 'is_active']);
            $table->index(['quantity_on_hand', 'quantity_reserved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
