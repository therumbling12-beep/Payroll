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
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('loan_type'); // 'sss_salary_loan', 'sss_calamity_loan', 'hdmf_multi_purpose_loan', 'hdmf_housing_loan', 'company_emergency_loan'
            $table->string('reference_no')->unique();
            $table->decimal('principal_amount', 12, 2);
            $table->decimal('total_amount_due', 12, 2);
            $table->smallInteger('term_months');
            $table->decimal('semi_monthly_amortization', 12, 2);
            $table->decimal('total_paid', 12, 2)->default(0.00);
            $table->decimal('remaining_balance', 12, 2);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status')->default('active'); // 'active', 'fully_paid', 'paused'
            $table->timestamps();
        });

        Schema::create('loan_amortization_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_loan_id')->constrained('employee_loans')->cascadeOnDelete();
            $table->foreignId('salary_computation_id')->nullable()->constrained('salary_computations')->nullOnDelete();
            $table->string('cutoff_period');
            $table->decimal('amount_deducted', 12, 2);
            $table->decimal('remaining_balance_after', 12, 2);
            $table->timestamp('deducted_at')->useCurrent();
            $table->timestamps();
        });

        Schema::table('salary_computations', function (Blueprint $table) {
            $table->decimal('driver_trip_incentive', 12, 2)->default(0.00)->after('trip_earnings');
            $table->decimal('loan_deduction', 12, 2)->default(0.00)->after('platform_fee_deduction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_computations', function (Blueprint $table) {
            $table->dropColumn([
                'driver_trip_incentive',
                'loan_deduction',
            ]);
        });

        Schema::dropIfExists('loan_amortization_logs');
        Schema::dropIfExists('employee_loans');
    }
};
