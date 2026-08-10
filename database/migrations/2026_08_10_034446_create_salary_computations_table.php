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
        Schema::create('salary_computations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('cutoff_period');
            $table->decimal('base_pay', 10, 2)->default(0.00);
            $table->decimal('trip_earnings', 10, 2)->default(0.00);
            $table->decimal('performance_bonus', 10, 2)->default(0.00);
            $table->decimal('gross_pay', 10, 2)->default(0.00);
            $table->decimal('sss_deduction', 10, 2)->default(0.00);
            $table->decimal('philhealth_deduction', 10, 2)->default(0.00);
            $table->decimal('pagibig_deduction', 10, 2)->default(0.00);
            $table->decimal('total_deductions', 10, 2)->default(0.00);
            $table->decimal('net_pay', 10, 2)->default(0.00);
            $table->enum('status', ['pending_approval', 'approved_legal', 'released_financial', 'rejected'])->default('pending_approval');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_computations');
    }
};
