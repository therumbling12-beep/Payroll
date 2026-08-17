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
        if (Schema::hasTable('compensation_adjustments')) {
            Schema::table('compensation_adjustments', function (Blueprint $table) {
                if (! Schema::hasColumn('compensation_adjustments', 'mode')) {
                    $table->string('mode', 20)->default('mode_a')->after('type'); // mode_a (auto), mode_b (manual)
                }
                if (! Schema::hasColumn('compensation_adjustments', 'monthly_ctc')) {
                    $table->decimal('monthly_ctc', 12, 2)->nullable()->after('new_rate');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'annual_ctc')) {
                    $table->decimal('annual_ctc', 12, 2)->nullable()->after('monthly_ctc');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'thirteenth_month_liability')) {
                    $table->decimal('thirteenth_month_liability', 12, 2)->nullable()->after('annual_ctc');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'employer_statutory_total')) {
                    $table->decimal('employer_statutory_total', 12, 2)->nullable()->after('thirteenth_month_liability');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'signing_bonus')) {
                    $table->decimal('signing_bonus', 12, 2)->nullable()->default(0.00)->after('bonus_amount');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'transport_allowance')) {
                    $table->decimal('transport_allowance', 12, 2)->nullable()->default(0.00)->after('allowance_amount');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'meal_allowance')) {
                    $table->decimal('meal_allowance', 12, 2)->nullable()->default(0.00)->after('transport_allowance');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'comms_allowance')) {
                    $table->decimal('comms_allowance', 12, 2)->nullable()->default(0.00)->after('meal_allowance');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'internal_equity_status')) {
                    $table->string('internal_equity_status', 50)->nullable()->default('NORMAL')->after('status');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'peer_median_salary')) {
                    $table->decimal('peer_median_salary', 12, 2)->nullable()->after('internal_equity_status');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'wage_distortion_variance_pct')) {
                    $table->decimal('wage_distortion_variance_pct', 5, 2)->nullable()->default(0.00)->after('peer_median_salary');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'budget_impact_status')) {
                    $table->string('budget_impact_status', 50)->nullable()->default('PENDING_FINANCE_VALIDATION')->after('wage_distortion_variance_pct');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('compensation_adjustments')) {
            Schema::table('compensation_adjustments', function (Blueprint $table) {
                $columns = [
                    'mode',
                    'monthly_ctc',
                    'annual_ctc',
                    'thirteenth_month_liability',
                    'employer_statutory_total',
                    'signing_bonus',
                    'transport_allowance',
                    'meal_allowance',
                    'comms_allowance',
                    'internal_equity_status',
                    'peer_median_salary',
                    'wage_distortion_variance_pct',
                    'budget_impact_status',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('compensation_adjustments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
