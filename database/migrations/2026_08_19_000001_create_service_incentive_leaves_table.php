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
        Schema::create('service_incentive_leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->unsignedSmallInteger('year')->index();
            $table->unsignedTinyInteger('entitled_days')->default(5);
            $table->unsignedTinyInteger('used_days')->default(0);
            $table->unsignedTinyInteger('cash_converted_days')->default(0);
            $table->decimal('cash_converted_amount', 12, 2)->default(0.00);
            $table->string('status', 30)->default('active'); // active, converted, closed
            $table->text('notes')->nullable();
            $table->json('leave_logs')->nullable(); // Array of [{date, days, notes, logged_at}]
            $table->timestamps();

            $table->unique(['employee_id', 'year'], 'sil_employee_year_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_incentive_leaves');
    }
};
