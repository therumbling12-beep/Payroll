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
        Schema::table('hmo_enrollments', function (Blueprint $table) {
            $table->string('hmo_provider')->default('Medicard')->after('hmo_card_number');
            $table->string('coverage_tier')->default('Basic')->after('provider_plan');
            $table->date('coverage_start_date')->nullable()->after('coverage_tier');
            $table->date('coverage_end_date')->nullable()->after('coverage_start_date');
            $table->decimal('annual_limit', 12, 2)->default(100000.00)->after('mbl_amount');
            $table->decimal('monthly_premium', 10, 2)->default(500.00)->after('annual_limit');
            $table->unsignedTinyInteger('dependent_count')->default(0)->after('monthly_premium');
            $table->text('notes')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hmo_enrollments', function (Blueprint $table) {
            $table->dropColumn([
                'hmo_provider',
                'coverage_tier',
                'coverage_start_date',
                'coverage_end_date',
                'annual_limit',
                'monthly_premium',
                'dependent_count',
                'notes',
            ]);
        });
    }
};
