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
            $table->timestamp('supervisor_approved_at')->nullable()->after('status');
            $table->text('supervisor_remarks')->nullable()->after('supervisor_approved_at');
            $table->text('rejection_reason')->nullable()->after('finance_remarks');
            $table->string('rejected_by')->nullable()->after('rejection_reason');
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->boolean('is_duplicate_flagged')->default(false)->after('validation_status');
            $table->integer('duplicate_risk_score')->default(0)->after('is_duplicate_flagged');
            $table->json('duplicate_match_details')->nullable()->after('duplicate_risk_score');
            $table->timestamp('paid_at')->nullable()->after('payroll_queued_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn([
                'supervisor_approved_at',
                'supervisor_remarks',
                'rejection_reason',
                'rejected_by',
                'rejected_at',
                'is_duplicate_flagged',
                'duplicate_risk_score',
                'duplicate_match_details',
                'paid_at',
            ]);
        });
    }
};
