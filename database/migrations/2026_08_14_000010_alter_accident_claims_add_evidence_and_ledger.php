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
        Schema::table('accident_claims', function (Blueprint $table) {
            if (! Schema::hasColumn('accident_claims', 'police_report_path')) {
                $table->string('police_report_path')->nullable()->after('document_path');
            }
            if (! Schema::hasColumn('accident_claims', 'medical_receipt_path')) {
                $table->string('medical_receipt_path')->nullable()->after('police_report_path');
            }
            if (! Schema::hasColumn('accident_claims', 'incident_photo_path')) {
                $table->string('incident_photo_path')->nullable()->after('medical_receipt_path');
            }
            if (! Schema::hasColumn('accident_claims', 'vehicle_plate_number')) {
                $table->string('vehicle_plate_number')->nullable()->after('incident_photo_path');
            }
            if (! Schema::hasColumn('accident_claims', 'trip_id')) {
                $table->string('trip_id')->nullable()->after('vehicle_plate_number');
            }
            if (! Schema::hasColumn('accident_claims', 'diagnosis')) {
                $table->text('diagnosis')->nullable()->after('trip_id');
            }
            if (! Schema::hasColumn('accident_claims', 'returned_by')) {
                $table->string('returned_by')->nullable()->after('diagnosis');
            }
            if (! Schema::hasColumn('accident_claims', 'return_reason')) {
                $table->text('return_reason')->nullable()->after('returned_by');
            }
            if (! Schema::hasColumn('accident_claims', 'returned_at')) {
                $table->timestamp('returned_at')->nullable()->after('return_reason');
            }
        });

        if (! Schema::hasTable('driver_pool_ledgers')) {
            Schema::create('driver_pool_ledgers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
                $table->string('entry_type')->default('driver_contribution'); // driver_contribution, company_subsidy_match, claim_disbursement, adjustment
                $table->decimal('amount', 12, 2)->default(0.00);
                $table->decimal('running_balance', 12, 2)->default(0.00);
                $table->string('reference_code');
                $table->string('description');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_pool_ledgers');

        Schema::table('accident_claims', function (Blueprint $table) {
            $table->dropColumn([
                'police_report_path',
                'medical_receipt_path',
                'incident_photo_path',
                'vehicle_plate_number',
                'trip_id',
                'diagnosis',
                'returned_by',
                'return_reason',
                'returned_at',
            ]);
        });
    }
};
