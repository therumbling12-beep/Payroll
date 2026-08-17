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
            if (! Schema::hasColumn('hmo_enrollments', 'enrollment_status')) {
                $table->string('enrollment_status')->default('active')->after('status');
            }
            if (! Schema::hasColumn('hmo_enrollments', 'hr_reviewed_at')) {
                $table->timestamp('hr_reviewed_at')->nullable()->after('enrollment_status');
            }
            if (! Schema::hasColumn('hmo_enrollments', 'hr_remarks')) {
                $table->text('hr_remarks')->nullable()->after('hr_reviewed_at');
            }
            if (! Schema::hasColumn('hmo_enrollments', 'budget_requisition_id')) {
                $table->foreignId('budget_requisition_id')->nullable()->after('hr_remarks')->constrained('budget_requisitions')->nullOnDelete();
            }
            if (! Schema::hasColumn('hmo_enrollments', 'id_photo_path')) {
                $table->string('id_photo_path')->nullable()->after('budget_requisition_id');
            }
            if (! Schema::hasColumn('hmo_enrollments', 'marriage_cert_path')) {
                $table->string('marriage_cert_path')->nullable()->after('id_photo_path');
            }
            if (! Schema::hasColumn('hmo_enrollments', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('marriage_cert_path');
            }
            if (! Schema::hasColumn('hmo_enrollments', 'renewed_at')) {
                $table->timestamp('renewed_at')->nullable()->after('rejection_reason');
            }
        });

        if (! Schema::hasTable('hmo_dependents')) {
            Schema::create('hmo_dependents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hmo_enrollment_id')->constrained('hmo_enrollments')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('full_name');
                $table->string('relationship')->default('Child'); // Spouse, Child, Parent
                $table->date('birth_date')->nullable();
                $table->string('gender')->nullable(); // Male, Female
                $table->string('birth_cert_path')->nullable();
                $table->string('status')->default('pending'); // pending, verified, rejected
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hmo_dependents');

        Schema::table('hmo_enrollments', function (Blueprint $table) {
            $table->dropForeign(['budget_requisition_id']);
            $table->dropColumn([
                'enrollment_status',
                'hr_reviewed_at',
                'hr_remarks',
                'budget_requisition_id',
                'id_photo_path',
                'marriage_cert_path',
                'rejection_reason',
                'renewed_at',
            ]);
        });
    }
};
