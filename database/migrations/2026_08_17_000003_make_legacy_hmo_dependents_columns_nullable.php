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
        if (Schema::hasTable('hmo_dependents')) {
            Schema::table('hmo_dependents', function (Blueprint $table) {
                if (Schema::hasColumn('hmo_dependents', 'first_name')) {
                    $table->string('first_name')->nullable()->change();
                }
                if (Schema::hasColumn('hmo_dependents', 'last_name')) {
                    $table->string('last_name')->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
