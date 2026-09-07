<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('safety_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('completed_by')->constrained('users')->cascadeOnDelete();
            $table->date('check_date');
            $table->enum('status', ['pass', 'fail', 'partial'])->default('pass');
            $table->json('items'); // Array of {item, passed, notes}
            $table->text('overall_notes')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'check_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('safety_checklists');
    }
};
