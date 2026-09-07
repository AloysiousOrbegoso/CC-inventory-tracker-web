<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ingredient_supplier', function (Blueprint $table) {
            $table->decimal('package_size', 12, 3)->nullable()->after('is_primary');
            $table->string('package_unit', 10)->nullable()->after('package_size');
            $table->decimal('package_price', 10, 2)->nullable()->after('package_unit');
            $table->decimal('delivery_fee', 10, 2)->nullable()->after('package_price');
            $table->text('pricing_notes')->nullable()->after('delivery_fee');
        });
    }

    public function down(): void
    {
        Schema::table('ingredient_supplier', function (Blueprint $table) {
            $table->dropColumn(['package_size', 'package_unit', 'package_price', 'delivery_fee', 'pricing_notes']);
        });
    }
};
