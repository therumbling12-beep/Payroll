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
        Schema::create('thirteenth_month_batches', function (Blueprint $table) {
            $table->id();
            $table->integer('year')->unique();
            $table->string('status')->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->timestamp('submitted_to_admin_at')->nullable();
            $table->timestamp('approved_by_admin_at')->nullable();
            $table->timestamp('budget_requested_at')->nullable();
            $table->timestamp('budget_received_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thirteenth_month_batches');
    }
};
