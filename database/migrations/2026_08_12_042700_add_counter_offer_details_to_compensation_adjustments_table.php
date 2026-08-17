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
        Schema::table('compensation_adjustments', function (Blueprint $table) {
            $table->foreignId('employee_id')->nullable()->change();
            $table->string('subject_type')->default('employee')->after('type'); // employee, applicant
            $table->string('applicant_name')->nullable()->after('subject_type');
            $table->string('applicant_position')->nullable()->after('applicant_name');
            $table->string('competitor_company')->nullable()->after('applicant_position');
            $table->decimal('competitor_offer', 10, 2)->nullable()->after('competitor_company');
            $table->unsignedInteger('urgency_days')->default(7)->after('competitor_offer');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('compensation_adjustments', function (Blueprint $table) {
            $table->dropColumn([
                'subject_type',
                'applicant_name',
                'applicant_position',
                'competitor_company',
                'competitor_offer',
                'urgency_days',
            ]);
        });
    }
};
