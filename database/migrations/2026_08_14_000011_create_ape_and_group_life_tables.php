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
        if (! Schema::hasTable('annual_physical_exams')) {
            Schema::create('annual_physical_exams', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->integer('exam_year')->default((int) date('Y'));
                $table->date('schedule_date');
                $table->string('time_slot')->default('08:00 AM - 10:00 AM');
                $table->string('facility_name')->default("St. Luke's Medical Center - BGC");
                $table->string('package_type')->default('Standard Occupational'); // Standard Occupational, Executive Comprehensive, Driver Road Fit
                $table->string('attendance_status')->default('scheduled'); // scheduled, attended, no_show, waived, rescheduled
                $table->string('medical_clearance_status')->default('pending_results'); // pending_results, fit_to_work, fit_with_restrictions, temporarily_unfit
                $table->text('findings_summary')->nullable();
                $table->string('medical_certificate_path')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('group_life_policies')) {
            Schema::create('group_life_policies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('policy_number');
                $table->string('provider_name')->default('Sun Life Grepa Financial');
                $table->string('coverage_type')->default('Group Term Life'); // Group Term Life, Accidental Death & Dismemberment, Total & Permanent Disability, Critical Illness Rider
                $table->decimal('sum_assured', 12, 2)->default(500000.00);
                $table->decimal('monthly_premium', 12, 2)->default(350.00);
                $table->decimal('company_shoulder_pct', 5, 2)->default(100.00);
                $table->string('beneficiary_primary_name');
                $table->string('beneficiary_primary_relation');
                $table->string('beneficiary_secondary_name')->nullable();
                $table->string('beneficiary_secondary_relation')->nullable();
                $table->date('policy_start_date');
                $table->date('policy_end_date');
                $table->string('status')->default('active'); // active, lapsed, claimed, terminated
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_life_policies');
        Schema::dropIfExists('annual_physical_exams');
    }
};
