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
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'hire_date')) {
                $table->date('hire_date')->nullable();
            }
            if (! Schema::hasColumn('employees', 'regularization_date')) {
                $table->date('regularization_date')->nullable();
            }
            if (! Schema::hasColumn('employees', 'employment_status')) {
                $table->string('employment_status')->default('regular');
            }
            if (! Schema::hasColumn('employees', 'current_step')) {
                $table->unsignedInteger('current_step')->default(1);
            }
            if (! Schema::hasColumn('employees', 'step_status')) {
                $table->string('step_status')->default('normal');
            }
            if (! Schema::hasColumn('employees', 'step_hold_reason')) {
                $table->string('step_hold_reason')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $cols = [];
            foreach (['hire_date', 'regularization_date', 'employment_status', 'current_step', 'step_status', 'step_hold_reason'] as $col) {
                if (Schema::hasColumn('employees', $col)) {
                    $cols[] = $col;
                }
            }
            if (! empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
