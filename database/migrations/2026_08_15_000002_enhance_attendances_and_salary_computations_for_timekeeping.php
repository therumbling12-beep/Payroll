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
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('regular_hours', 8, 2)->default(88.00)->after('days_worked');
            $table->decimal('overtime_hours', 8, 2)->default(0.00)->after('regular_hours');
            $table->decimal('night_diff_hours', 8, 2)->default(0.00)->after('overtime_hours');
            $table->decimal('rest_day_hours', 8, 2)->default(0.00)->after('night_diff_hours');
            $table->decimal('holiday_regular_hours', 8, 2)->default(0.00)->after('rest_day_hours');
            $table->decimal('holiday_special_hours', 8, 2)->default(0.00)->after('holiday_regular_hours');
            $table->integer('tardiness_minutes')->default(0)->after('holiday_special_hours');
            $table->integer('undertime_minutes')->default(0)->after('tardiness_minutes');
        });

        Schema::table('salary_computations', function (Blueprint $table) {
            $table->decimal('holiday_pay', 12, 2)->default(0.00)->after('trip_earnings');
            $table->decimal('overtime_pay', 12, 2)->default(0.00)->after('holiday_pay');
            $table->decimal('night_diff_pay', 12, 2)->default(0.00)->after('overtime_pay');
            $table->decimal('tardiness_deduction', 12, 2)->default(0.00)->after('withholding_tax');
            $table->decimal('undertime_deduction', 12, 2)->default(0.00)->after('tardiness_deduction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'regular_hours',
                'overtime_hours',
                'night_diff_hours',
                'rest_day_hours',
                'holiday_regular_hours',
                'holiday_special_hours',
                'tardiness_minutes',
                'undertime_minutes',
            ]);
        });

        Schema::table('salary_computations', function (Blueprint $table) {
            $table->dropColumn([
                'holiday_pay',
                'overtime_pay',
                'night_diff_pay',
                'tardiness_deduction',
                'undertime_deduction',
            ]);
        });
    }
};
