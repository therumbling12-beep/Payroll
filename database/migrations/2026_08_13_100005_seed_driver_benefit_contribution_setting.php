<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $exists = DB::table('company_settings')->where('key', 'driver_benefit_contribution_rate')->exists();
        if (!$exists) {
            DB::table('company_settings')->insert([
                'key' => 'driver_benefit_contribution_rate',
                'value' => '0.03',
                'description' => 'Driver Benefit Fund Contribution Rate (3% deducted from driver payroll into accident & benefits pool)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('company_settings')->where('key', 'driver_benefit_contribution_rate')->delete();
    }
};
