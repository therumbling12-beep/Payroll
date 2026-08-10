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
        Schema::create('budget_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('requisition_code');
            $table->string('category');
            $table->decimal('amount', 12, 2);
            $table->text('justification')->nullable();
            $table->string('status')->default('awaiting_approval');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_requisitions');
    }
};
