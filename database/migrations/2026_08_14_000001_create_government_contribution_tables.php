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
        if (! Schema::hasTable('government_contribution_tables')) {
            Schema::create('government_contribution_tables', function (Blueprint $table) {
                $table->id();
                $table->string('table_type'); // 'SSS', 'PHILHEALTH', 'PAGIBIG', 'BIR_TRAIN'
                $table->integer('effective_year')->default(2026);
                $table->decimal('bracket_from', 12, 2)->default(0.00);
                $table->decimal('bracket_to', 12, 2)->nullable();
                $table->decimal('monthly_salary_credit', 12, 2)->nullable();
                $table->decimal('employee_rate', 6, 4)->nullable();
                $table->decimal('employer_rate', 6, 4)->nullable();
                $table->decimal('employee_fixed_amount', 12, 2)->nullable();
                $table->decimal('employer_fixed_amount', 12, 2)->nullable();
                $table->decimal('ec_contribution', 12, 2)->default(0.00);
                $table->decimal('base_tax', 12, 2)->default(0.00);
                $table->decimal('excess_rate', 6, 4)->default(0.00);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('minimum_wage_orders')) {
            Schema::create('minimum_wage_orders', function (Blueprint $table) {
                $table->id();
                $table->string('region_code');
                $table->string('region_name');
                $table->string('wage_order_number');
                $table->decimal('daily_rate', 10, 2);
                $table->decimal('monthly_rate_equivalent', 10, 2);
                $table->date('effective_date');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('salary_computations', function (Blueprint $table) {
            if (! Schema::hasColumn('salary_computations', 'sss_employer')) {
                $table->decimal('sss_employer', 10, 2)->default(0.00)->after('sss_deduction');
            }
            if (! Schema::hasColumn('salary_computations', 'philhealth_employer')) {
                $table->decimal('philhealth_employer', 10, 2)->default(0.00)->after('philhealth_deduction');
            }
            if (! Schema::hasColumn('salary_computations', 'pagibig_employer')) {
                $table->decimal('pagibig_employer', 10, 2)->default(0.00)->after('pagibig_deduction');
            }
            if (! Schema::hasColumn('salary_computations', 'ec_contribution')) {
                $table->decimal('ec_contribution', 10, 2)->default(0.00)->after('pagibig_employer');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_computations', function (Blueprint $table) {
            $table->dropColumn(['sss_employer', 'philhealth_employer', 'pagibig_employer', 'ec_contribution']);
        });
        Schema::dropIfExists('minimum_wage_orders');
        Schema::dropIfExists('government_contribution_tables');
    }
};
