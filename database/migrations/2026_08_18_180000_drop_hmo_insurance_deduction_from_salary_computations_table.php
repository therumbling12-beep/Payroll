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
        if (Schema::hasColumn('salary_computations', 'hmo_insurance_deduction')) {
            Schema::table('salary_computations', function (Blueprint $table) {
                $table->dropColumn('hmo_insurance_deduction');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('salary_computations', 'hmo_insurance_deduction')) {
            Schema::table('salary_computations', function (Blueprint $table) {
                $table->decimal('hmo_insurance_deduction', 10, 2)->default(0.00)->after('pagibig_deduction');
            });
        }
    }
};
