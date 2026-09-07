<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('street_address')->nullable()->after('location');
            $table->string('city')->nullable()->after('street_address');
            $table->string('province')->nullable()->after('city');
            $table->string('zip_code')->nullable()->after('province');
            $table->string('phone')->nullable()->after('zip_code');
            $table->string('email')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['street_address', 'city', 'province', 'zip_code', 'phone', 'email']);
        });
    }
};
