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
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('type'); // expense, incentive, maternity
            $table->decimal('amount', 10, 2);
            $table->string('cutoff_period')->default('2026-07-01_15');
            $table->text('description')->nullable();
            $table->string('receipt_number')->nullable();
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->date('effective_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
