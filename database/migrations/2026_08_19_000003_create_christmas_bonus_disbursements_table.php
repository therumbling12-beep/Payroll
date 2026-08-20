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
        Schema::create('christmas_bonus_disbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedSmallInteger('bonus_year')->index();
            $table->decimal('base_bonus_amount', 12, 2)->default(2000.00);
            $table->decimal('months_tenure', 4, 1)->default(0.0);
            $table->boolean('is_prorated')->default(false);
            $table->decimal('calculated_bonus_amount', 12, 2)->default(0.00);
            $table->string('status', 30)->default('pending'); // pending, hr_approved, released_to_payroll
            $table->timestamp('released_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'bonus_year'], 'xmas_emp_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('christmas_bonus_disbursements');
    }
};
