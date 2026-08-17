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
        if (! Schema::hasTable('hmo_dependents')) {
            Schema::create('hmo_dependents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hmo_enrollment_id')->constrained('hmo_enrollments')->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->string('full_name');
                $table->string('relationship')->default('Child');
                $table->date('birth_date')->nullable();
                $table->string('gender')->nullable();
                $table->string('birth_cert_path')->nullable();
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        } else {
            Schema::table('hmo_dependents', function (Blueprint $table) {
                if (! Schema::hasColumn('hmo_dependents', 'employee_id')) {
                    $table->foreignId('employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
                }
                if (! Schema::hasColumn('hmo_dependents', 'full_name')) {
                    $table->string('full_name')->default('');
                }
                if (! Schema::hasColumn('hmo_dependents', 'relationship')) {
                    $table->string('relationship')->default('Child');
                }
                if (! Schema::hasColumn('hmo_dependents', 'birth_date')) {
                    $table->date('birth_date')->nullable();
                }
                if (! Schema::hasColumn('hmo_dependents', 'gender')) {
                    $table->string('gender')->nullable();
                }
                if (! Schema::hasColumn('hmo_dependents', 'birth_cert_path')) {
                    $table->string('birth_cert_path')->nullable();
                }
                if (! Schema::hasColumn('hmo_dependents', 'status')) {
                    $table->string('status')->default('pending');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op for safety
    }
};
