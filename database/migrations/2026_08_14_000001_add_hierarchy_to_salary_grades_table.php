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
        if (Schema::hasTable('salary_grades')) {
            Schema::table('salary_grades', function (Blueprint $table) {
                if (! Schema::hasColumn('salary_grades', 'grade_code')) {
                    $table->string('grade_code', 20)->nullable()->after('id');
                }
                if (! Schema::hasColumn('salary_grades', 'job_level')) {
                    $table->string('job_level', 50)->nullable()->after('grade_code');
                }
                if (! Schema::hasColumn('salary_grades', 'sample_positions')) {
                    $table->text('sample_positions')->nullable()->after('position_name');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('salary_grades')) {
            Schema::table('salary_grades', function (Blueprint $table) {
                if (Schema::hasColumn('salary_grades', 'grade_code')) {
                    $table->dropColumn('grade_code');
                }
                if (Schema::hasColumn('salary_grades', 'job_level')) {
                    $table->dropColumn('job_level');
                }
                if (Schema::hasColumn('salary_grades', 'sample_positions')) {
                    $table->dropColumn('sample_positions');
                }
            });
        }
    }
};
