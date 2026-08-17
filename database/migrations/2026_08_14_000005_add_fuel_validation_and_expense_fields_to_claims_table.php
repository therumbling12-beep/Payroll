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
        Schema::table('claims', function (Blueprint $table) {
            $table->decimal('distance_traveled_km', 8, 2)->nullable()->after('amount');
            $table->decimal('vehicle_fuel_efficiency_kpl', 5, 2)->nullable()->default(10.00)->after('distance_traveled_km');
            $table->decimal('fuel_liters', 8, 2)->nullable()->after('vehicle_fuel_efficiency_kpl');
            $table->decimal('fuel_pump_price', 8, 2)->nullable()->after('fuel_liters');
            $table->decimal('expected_fuel_cost', 10, 2)->nullable()->after('fuel_pump_price');
            $table->decimal('fuel_variance_pct', 5, 2)->nullable()->after('expected_fuel_cost');
            $table->boolean('auto_validated')->default(false)->after('fuel_variance_pct');
            $table->string('validation_status')->default('standard')->after('auto_validated'); // auto_verified, flagged_variance, standard
            $table->string('merchant_tin')->nullable()->after('receipt_number');
            $table->string('merchant_name')->nullable()->after('merchant_tin');
            $table->string('expense_subtype')->nullable()->after('merchant_name'); // fuel, toll, maintenance, parking, meal, communication, other
            $table->decimal('odometer_start', 10, 2)->nullable()->after('expense_subtype');
            $table->decimal('odometer_end', 10, 2)->nullable()->after('odometer_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn([
                'distance_traveled_km',
                'vehicle_fuel_efficiency_kpl',
                'fuel_liters',
                'fuel_pump_price',
                'expected_fuel_cost',
                'fuel_variance_pct',
                'auto_validated',
                'validation_status',
                'merchant_tin',
                'merchant_name',
                'expense_subtype',
                'odometer_start',
                'odometer_end',
            ]);
        });
    }
};
