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
        if (Schema::hasTable('compensation_adjustments')) {
            Schema::table('compensation_adjustments', function (Blueprint $table) {
                if (! Schema::hasColumn('compensation_adjustments', 'admin_approval_status')) {
                    $table->string('admin_approval_status', 50)->nullable()->default('PENDING_ADMIN_APPROVAL')->after('budget_impact_status');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'admin_approved_by')) {
                    $table->string('admin_approved_by', 100)->nullable()->after('admin_approval_status');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'admin_approved_at')) {
                    $table->timestamp('admin_approved_at')->nullable()->after('admin_approved_by');
                }
                if (! Schema::hasColumn('compensation_adjustments', 'admin_notes')) {
                    $table->text('admin_notes')->nullable()->after('admin_approved_at');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('compensation_adjustments')) {
            Schema::table('compensation_adjustments', function (Blueprint $table) {
                $columns = [
                    'admin_approval_status',
                    'admin_approved_by',
                    'admin_approved_at',
                    'admin_notes',
                ];
                foreach ($columns as $column) {
                    if (Schema::hasColumn('compensation_adjustments', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
