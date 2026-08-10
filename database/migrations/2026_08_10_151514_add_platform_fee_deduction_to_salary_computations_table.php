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
        Schema::table('salary_computations', function (Blueprint $table) {
            $table->decimal('platform_fee_deduction', 10, 2)->default(0.00)->after('hmo_insurance_deduction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_computations', function (Blueprint $table) {
            $table->dropColumn('platform_fee_deduction');
        });
    }
};
