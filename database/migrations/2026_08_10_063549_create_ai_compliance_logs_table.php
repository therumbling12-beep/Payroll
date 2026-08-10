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
        Schema::create('ai_compliance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('salary_computation_id')->constrained('salary_computations')->onDelete('cascade');
            $table->integer('compliance_score')->default(100);
            $table->json('flagged_issues')->nullable();
            $table->text('ai_summary')->nullable();
            $table->string('status')->default('PASSED'); // PASSED, WARNING, CRITICAL
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_compliance_logs');
    }
};
