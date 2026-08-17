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
        Schema::table('accident_claims', function (Blueprint $table) {
            $table->date('incident_date')->nullable()->after('incident_number');
            $table->string('incident_type')->default('Work Injury')->after('incident_date');
            $table->decimal('approved_amount', 12, 2)->nullable()->after('bill_amount');
            $table->string('hr_status')->default('pending')->after('approved_amount');
            $table->text('hr_remarks')->nullable()->after('hr_status');
            $table->timestamp('hr_reviewed_at')->nullable()->after('hr_remarks');
            $table->string('admin_status')->default('pending')->after('hr_reviewed_at');
            $table->text('admin_remarks')->nullable()->after('admin_status');
            $table->timestamp('admin_reviewed_at')->nullable()->after('admin_remarks');
            $table->string('finance_status')->default('pending')->after('admin_reviewed_at');
            $table->text('finance_remarks')->nullable()->after('finance_status');
            $table->timestamp('finance_reviewed_at')->nullable()->after('finance_remarks');
            $table->string('workflow_status')->default('pending_hr')->after('finance_reviewed_at');
            $table->boolean('documents_uploaded')->default(false)->after('workflow_status');
            $table->string('document_path')->nullable()->after('documents_uploaded');
        });

        // Set existing records that had status = 'paid' to approved
        DB::table('accident_claims')->where('status', 'paid')->update([
            'workflow_status' => 'approved',
            'hr_status' => 'approved',
            'admin_status' => 'approved',
            'finance_status' => 'approved',
            'approved_amount' => DB::raw('bill_amount'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accident_claims', function (Blueprint $table) {
            $table->dropColumn([
                'incident_date',
                'incident_type',
                'approved_amount',
                'hr_status',
                'hr_remarks',
                'hr_reviewed_at',
                'admin_status',
                'admin_remarks',
                'admin_reviewed_at',
                'finance_status',
                'finance_remarks',
                'finance_reviewed_at',
                'workflow_status',
                'documents_uploaded',
                'document_path',
            ]);
        });
    }
};
