<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_logs', function (Blueprint $table) {
            $table->decimal('opening_till_amount', 10, 2)->nullable()->after('status');
            $table->timestamp('prep_completed_at')->nullable()->after('opening_till_amount');
            $table->timestamp('cleaning_completed_at')->nullable()->after('prep_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('shift_logs', function (Blueprint $table) {
            $table->dropColumn(['opening_till_amount', 'prep_completed_at', 'cleaning_completed_at']);
        });
    }
};
