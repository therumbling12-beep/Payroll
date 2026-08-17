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
        Schema::table('claim_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('claim_categories', 'tax_classification')) {
                $table->string('tax_classification')->default('non_taxable')->after('type'); // non_taxable, de_minimis, taxable
            }
            if (! Schema::hasColumn('claim_categories', 'de_minimis_annual_cap')) {
                $table->decimal('de_minimis_annual_cap', 10, 2)->nullable()->after('max_amount');
            }
            if (! Schema::hasColumn('claim_categories', 'requires_receipt')) {
                $table->boolean('requires_receipt')->default(true)->after('is_active');
            }
            if (! Schema::hasColumn('claim_categories', 'spending_limit_period')) {
                $table->string('spending_limit_period')->default('per_claim')->after('de_minimis_annual_cap'); // per_claim, per_month, per_year
            }
        });

        Schema::table('claims', function (Blueprint $table) {
            if (! Schema::hasColumn('claims', 'non_taxable_amount')) {
                $table->decimal('non_taxable_amount', 10, 2)->default(0.00)->after('amount');
            }
            if (! Schema::hasColumn('claims', 'taxable_amount')) {
                $table->decimal('taxable_amount', 10, 2)->default(0.00)->after('non_taxable_amount');
            }
            if (! Schema::hasColumn('claims', 'tax_classification')) {
                $table->string('tax_classification')->nullable()->after('taxable_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('claim_categories', function (Blueprint $table) {
            $table->dropColumn([
                'tax_classification',
                'de_minimis_annual_cap',
                'requires_receipt',
                'spending_limit_period',
            ]);
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn([
                'non_taxable_amount',
                'taxable_amount',
                'tax_classification',
            ]);
        });
    }
};
