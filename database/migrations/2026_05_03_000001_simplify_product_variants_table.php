<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->mergeDuplicateAgeVariants();

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variant_unique');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn(['color_name', 'size_name', 'price', 'compare_price']);
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->unique(['product_id', 'age_label'], 'product_variant_age_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variant_age_unique');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('color_name')->default('');
            $table->string('size_name')->default('');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('compare_price', 10, 2)->nullable();
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->unique(['product_id', 'color_name', 'size_name', 'age_label'], 'product_variant_unique');
        });
    }

    private function mergeDuplicateAgeVariants(): void
    {
        $duplicateGroups = DB::table('product_variants')
            ->select('product_id', 'age_label')
            ->groupBy('product_id', 'age_label')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateGroups as $group) {
            $variants = DB::table('product_variants')
                ->where('product_id', $group->product_id)
                ->where('age_label', $group->age_label)
                ->orderBy('id')
                ->get();

            $keepVariant = $variants->first();
            $duplicateIds = $variants->pluck('id')->skip(1)->values();

            if ($duplicateIds->isEmpty()) {
                continue;
            }

            DB::table('order_items')
                ->whereIn('variant_id', $duplicateIds)
                ->update(['variant_id' => $keepVariant->id]);

            DB::table('stock_movements')
                ->whereIn('variant_id', $duplicateIds)
                ->update(['variant_id' => $keepVariant->id]);

            if (Schema::hasColumn('product_images', 'variant_id')) {
                DB::table('product_images')
                    ->whereIn('variant_id', $duplicateIds)
                    ->update(['variant_id' => null]);
            }

            DB::table('product_variants')
                ->where('id', $keepVariant->id)
                ->update([
                    'quantity_on_hand' => $variants->sum('quantity_on_hand'),
                    'quantity_reserved' => $variants->sum('quantity_reserved'),
                    'is_active' => $variants->contains(fn ($variant) => (bool) $variant->is_active),
                    'updated_at' => now(),
                ]);

            DB::table('product_variants')
                ->whereIn('id', $duplicateIds)
                ->delete();
        }
    }
};
