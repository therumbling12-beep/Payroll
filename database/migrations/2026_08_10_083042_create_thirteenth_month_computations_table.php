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
        Schema::create('thirteenth_month_computations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->decimal('monthly_salary', 12, 2)->default(0);
            $table->integer('months_worked')->default(12);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status')->default('pending_approval');
            $table->timestamps();

            $table->unique(['employee_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thirteenth_month_computations');
    }
};
