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
        if (Schema::hasTable('salary_grades')) {
            Schema::table('salary_grades', function (Blueprint $table) {
                if (! Schema::hasColumn('salary_grades', 'effectivity_date')) {
                    $table->date('effectivity_date')->nullable()->after('annual_growth_rate');
                }
            });
        }

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (! Schema::hasColumn('employees', 'performance_rating')) {
                    $table->string('performance_rating')->nullable()->default('Satisfactory')->after('employment_status');
                }
            });
        }

        if (Schema::hasTable('compensation_adjustments')) {
            Schema::table('compensation_adjustments', function (Blueprint $table) {
                if (! Schema::hasColumn('compensation_adjustments', 'employee_response')) {
                    $table->string('employee_response')->nullable()->default('pending_response')->after('status');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'expected_salary')) {
                    $table->decimal('expected_salary', 12, 2)->nullable()->after('competitor_offer');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'education_level')) {
                    $table->string('education_level')->nullable()->after('expected_salary');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'allowance_amount')) {
                    $table->decimal('allowance_amount', 12, 2)->nullable()->default(0.00)->after('bonus_amount');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'hmo_tier')) {
                    $table->string('hmo_tier')->nullable()->default('Individual')->after('allowance_amount');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('salary_grades')) {
            Schema::table('salary_grades', function (Blueprint $table) {
                if (Schema::hasColumn('salary_grades', 'effectivity_date')) {
                    $table->dropColumn('effectivity_date');
                }
            });
        }

        if (Schema::hasTable('employees')) {
            Schema::table('employees', function (Blueprint $table) {
                if (Schema::hasColumn('employees', 'performance_rating')) {
                    $table->dropColumn('performance_rating');
                }
            });
        }

        if (Schema::hasTable('compensation_adjustments')) {
            Schema::table('compensation_adjustments', function (Blueprint $table) {
                $cols = ['employee_response', 'expected_salary', 'education_level', 'allowance_amount', 'hmo_tier'];
                foreach ($cols as $col) {
                    if (Schema::hasColumn('compensation_adjustments', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }
    }
};
