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
            $table->foreignId('category_id')->nullable()->constrained('claim_categories')->nullOnDelete();
            $table->string('category')->nullable()->after('type');
            $table->string('approval_status')->default('pending_hr')->after('status');
            $table->text('hr_remarks')->nullable();
            $table->text('admin_remarks')->nullable();
            $table->text('finance_remarks')->nullable();
            $table->timestamp('hr_approved_at')->nullable();
            $table->timestamp('admin_approved_at')->nullable();
            $table->timestamp('finance_approved_at')->nullable();
            $table->timestamp('payroll_queued_at')->nullable();
            $table->date('expense_date')->nullable();
            $table->string('attachment_path')->nullable();
            $table->decimal('performance_rating', 3, 2)->nullable();
            $table->decimal('sss_maternity_share', 10, 2)->default(0.00);
            $table->decimal('company_maternity_topup', 10, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn([
                'category_id',
                'category',
                'approval_status',
                'hr_remarks',
                'admin_remarks',
                'finance_remarks',
                'hr_approved_at',
                'admin_approved_at',
                'finance_approved_at',
                'payroll_queued_at',
                'expense_date',
                'attachment_path',
                'performance_rating',
                'sss_maternity_share',
                'company_maternity_topup',
            ]);
        });
    }
};
