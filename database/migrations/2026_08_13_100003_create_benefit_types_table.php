<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('benefit_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->string('category'); // Health Insurance, Insurance, Government Mandated, Statutory
            $table->string('eligibility'); // Regular employees after regularization, All TNVS drivers, All employees from day 1, etc.
            $table->unsignedInteger('min_tenure_months')->default(0);
            $table->string('dependent_options')->default('Employee only'); // Employee only, Employee + 1, Employee + family
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed default benefit types based on v2.md Table 4.2
        DB::table('benefit_types')->insert([
            [
                'name' => 'HMO (General)',
                'code' => 'hmo_general',
                'category' => 'Health Insurance',
                'eligibility' => 'Regular employees after regularization',
                'min_tenure_months' => 6,
                'dependent_options' => 'Employee + Dependents',
                'description' => 'Comprehensive inpatient and outpatient medical coverage under Medicard.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'HMO (Driver-Specific)',
                'code' => 'hmo_driver',
                'category' => 'Health Insurance',
                'eligibility' => 'All active TNVS drivers',
                'min_tenure_months' => 0,
                'dependent_options' => 'Driver only',
                'description' => 'Accident, emergency hospitalization, and work injury medical coverage during trip operations.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Life Insurance',
                'code' => 'life_insurance',
                'category' => 'Insurance',
                'eligibility' => 'Regular employees',
                'min_tenure_months' => 6,
                'dependent_options' => 'Employee only',
                'description' => 'Group term life insurance and accidental death & dismemberment coverage.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dental Care',
                'code' => 'dental',
                'category' => 'Health Insurance',
                'eligibility' => 'Regular employees (optional add-on)',
                'min_tenure_months' => 6,
                'dependent_options' => 'Employee only',
                'description' => 'Annual dental checkups, cleaning, simple extractions, and emergency relief.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Optical Allowance',
                'code' => 'optical',
                'category' => 'Health Insurance',
                'eligibility' => 'Regular employees (optional add-on)',
                'min_tenure_months' => 6,
                'dependent_options' => 'Employee only',
                'description' => 'Annual prescription eyeglasses or contact lenses reimbursement allowance.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'SSS (Social Security System)',
                'code' => 'sss',
                'category' => 'Government Mandated',
                'eligibility' => 'All employees from day 1 (Drivers self-employed / EC Program)',
                'min_tenure_months' => 0,
                'dependent_options' => 'Family beneficiaries',
                'description' => 'Statutory social security, disability, retirement, and Employees Compensation program.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'PhilHealth',
                'code' => 'philhealth',
                'category' => 'Government Mandated',
                'eligibility' => 'All employees from day 1',
                'min_tenure_months' => 0,
                'dependent_options' => 'Declared dependents',
                'description' => 'Universal national health insurance program hospital subsidies.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pag-IBIG Fund (HDMF)',
                'code' => 'pagibig',
                'category' => 'Government Mandated',
                'eligibility' => 'All employees from day 1',
                'min_tenure_months' => 0,
                'dependent_options' => 'N/A',
                'description' => 'Home Development Mutual Fund provident savings and shelter financing.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '13th Month Pay',
                'code' => 'thirteenth_month',
                'category' => 'Statutory',
                'eligibility' => 'All regular and probationary rank-and-file employees',
                'min_tenure_months' => 1,
                'dependent_options' => 'N/A',
                'description' => 'Statutory mandatory year-end compensation benefit (Presidential Decree No. 851).',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Maternity Benefit (RA 11210)',
                'code' => 'maternity',
                'category' => 'Statutory',
                'eligibility' => 'Female employees with required SSS contributions',
                'min_tenure_months' => 0,
                'dependent_options' => 'Child',
                'description' => '105-day paid maternity leave with employer salary differential support.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Paternity Leave (RA 8187)',
                'code' => 'paternity',
                'category' => 'Statutory',
                'eligibility' => 'Married male regular and probationary employees',
                'min_tenure_months' => 0,
                'dependent_options' => 'Spouse',
                'description' => '7 days of paid leave for married male employees for each childbirth.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('benefit_types');
    }
};
