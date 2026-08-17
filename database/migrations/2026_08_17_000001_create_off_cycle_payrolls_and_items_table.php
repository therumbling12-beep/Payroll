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
        Schema::create('off_cycle_payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('run_number', 50)->unique();
            $table->string('run_type', 50); // final_pay, special_bonus, salary_differential, thirteenth_month_advance
            $table->string('title');
            $table->date('payout_date');
            $table->string('status', 30)->default('draft'); // draft, pending_approval, approved, released
            $table->decimal('total_gross', 14, 2)->default(0.00);
            $table->decimal('total_deductions', 14, 2)->default(0.00);
            $table->decimal('total_net_pay', 14, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        Schema::create('off_cycle_payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('off_cycle_payroll_id')->constrained('off_cycle_payrolls')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->decimal('basic_pay_earned', 12, 2)->default(0.00);
            $table->decimal('pro_rated_13th_month', 12, 2)->default(0.00);
            $table->decimal('leave_conversion_pay', 12, 2)->default(0.00);
            $table->decimal('bonuses_differentials', 12, 2)->default(0.00);
            $table->decimal('reimbursements', 12, 2)->default(0.00);
            $table->decimal('gross_amount', 12, 2)->default(0.00);
            $table->decimal('withholding_tax', 12, 2)->default(0.00);
            $table->decimal('loan_deduction', 12, 2)->default(0.00);
            $table->decimal('other_deductions', 12, 2)->default(0.00);
            $table->decimal('total_deductions', 12, 2)->default(0.00);
            $table->decimal('net_settlement_pay', 12, 2)->default(0.00);
            $table->json('computation_breakdown')->nullable();
            $table->string('status', 30)->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('off_cycle_payroll_items');
        Schema::dropIfExists('off_cycle_payrolls');
    }
};
