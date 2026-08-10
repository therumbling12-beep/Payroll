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
            $table->decimal('reimbursements', 10, 2)->default(0.00)->after('performance_bonus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_computations', function (Blueprint $table) {
            $table->dropColumn('reimbursements');
        });
    }
};
