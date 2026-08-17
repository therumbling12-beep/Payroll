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
        if (! Schema::hasTable('salary_steps')) {
            Schema::create('salary_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('salary_grade_id')->constrained('salary_grades')->cascadeOnDelete();
                $table->unsignedInteger('step_number'); // e.g. 1 to 7
                $table->decimal('years_required', 4, 1)->default(0.0); // e.g. 0, 1, 2, 3, 5, 7, 10
                $table->decimal('increment_percentage', 5, 2)->default(0.00); // e.g. 3.00, 5.00, 7.00, 10.00
                $table->decimal('base_amount', 10, 2)->nullable(); // fixed step base rate if applicable
                $table->timestamps();

                $table->unique(['salary_grade_id', 'step_number']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_steps');
    }
};
