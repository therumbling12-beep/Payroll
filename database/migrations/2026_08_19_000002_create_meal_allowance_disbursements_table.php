<?php

declare(strict_types=1);

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
        Schema::create('meal_allowance_disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('cutoff_period', 50)->index();
            $table->unsignedTinyInteger('days_rendered')->default(0);
            $table->decimal('daily_subsidy_rate', 10, 2)->default(60.00);
            $table->decimal('gross_amount', 12, 2)->default(0.00);
            $table->decimal('tax_exempt_amount', 12, 2)->default(0.00); // Within BIR RR 11-2018 De Minimis Cap
            $table->decimal('taxable_excess_amount', 12, 2)->default(0.00); // Excess subject to withholding tax
            $table->string('status', 30)->default('pending'); // pending, approved, released_to_payroll
            $table->timestamp('disbursed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'cutoff_period'], 'meal_emp_cutoff_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meal_allowance_disbursements');
    }
};
