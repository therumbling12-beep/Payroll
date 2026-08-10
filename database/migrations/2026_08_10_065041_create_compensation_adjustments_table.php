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
        Schema::create('compensation_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('type'); // merit_promotion, counter_offer, salary_config
            $table->decimal('old_rate', 10, 2)->nullable();
            $table->decimal('new_rate', 10, 2)->nullable();
            $table->decimal('bonus_amount', 10, 2)->default(0.00);
            $table->string('old_position')->nullable();
            $table->string('new_position')->nullable();
            $table->text('reason')->nullable();
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
        Schema::dropIfExists('compensation_adjustments');
    }
};
