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
        Schema::create('hmo_grade_limits', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('grade_min')->default(1);
            $table->unsignedInteger('grade_max')->default(2);
            $table->string('title');
            $table->decimal('mbl_amount', 12, 2)->default(100000.00);
            $table->string('room_and_board')->default('semi_private'); // semi_private, private, suite
            $table->unsignedInteger('max_dependents')->default(0);
            $table->decimal('dependent_premium_coshare_pct', 5, 2)->default(100.00); // % employee shoulders for dependents
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('accredited_facilities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('facility_type')->default('Hospital'); // Hospital, Clinic, Diagnostic Center, Emergency
            $table->string('region')->default('NCR');
            $table->string('address');
            $table->string('contact_number')->nullable();
            $table->boolean('is_emergency_ready')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accredited_facilities');
        Schema::dropIfExists('hmo_grade_limits');
    }
};
