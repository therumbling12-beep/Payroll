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
        Schema::table('ai_compliance_logs', function (Blueprint $table) {
            $table->json('resolution_suggestions')->nullable()->after('flagged_issues');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_compliance_logs', function (Blueprint $table) {
            $table->dropColumn('resolution_suggestions');
        });
    }
};
