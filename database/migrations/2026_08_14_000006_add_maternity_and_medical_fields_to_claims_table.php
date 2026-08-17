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
        Schema::table('claims', function (Blueprint $table) {
            $table->string('maternity_type')->nullable()->default('normal_caesarean')->after('company_maternity_topup'); // normal_caesarean, solo_parent, miscarriage
            $table->integer('maternity_leave_days')->default(105)->after('maternity_type');
            $table->string('sss_reimbursement_status')->default('pending_advance')->after('maternity_leave_days'); // pending_advance, advanced_to_employee, submitted_to_sss, reimbursed_by_sss
            $table->date('sss_reimbursement_date')->nullable()->after('sss_reimbursement_status');
            $table->string('sss_reference_number')->nullable()->after('sss_reimbursement_date');
            $table->string('medical_condition')->nullable()->after('sss_reference_number');
            $table->string('doctor_license_number')->nullable()->after('medical_condition');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn([
                'maternity_type',
                'maternity_leave_days',
                'sss_reimbursement_status',
                'sss_reimbursement_date',
                'sss_reference_number',
                'medical_condition',
                'doctor_license_number',
            ]);
        });
    }
};
