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
        Schema::create('hmo_utilization_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('hmo_enrollment_id')->nullable()->constrained('hmo_enrollments')->nullOnDelete();
            $table->string('benefit_type'); // e.g. HMO — ER Visit, Dental — Cleaning, Optical — Eyewear
            $table->string('service_provider')->nullable(); // e.g. St. Luke's Medical Center, Medicard Clinic
            $table->decimal('utilized_amount', 12, 2);
            $table->decimal('remaining_balance', 12, 2);
            $table->date('utilized_at');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hmo_utilization_logs');
    }
};
